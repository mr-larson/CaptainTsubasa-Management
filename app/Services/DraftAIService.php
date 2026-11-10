<?php

namespace App\Services;

use App\Enums\TeamStyle;
use App\Models\PlayerLink;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\Concerns\EvaluatesPlayers;

class DraftAIService
{
    use EvaluatesPlayers;

    // Composition cible par poste
    private const TARGET_BY_POSITION = [
        'GK'  => 2,
        'DEF' => 5,
        'MID' => 5,
        'ATT' => 4,
    ];

    // Stats clés par style tactique (pour scorer les joueurs)
    private const STYLE_STATS = [
        TeamStyle::TACTICAL_OFFENSIVE  => ['shot', 'dribble', 'attack', 'speed'],
        TeamStyle::TACTICAL_DEFENSIVE  => ['defense', 'tackle', 'block', 'intercept'],
        TeamStyle::TACTICAL_POSSESSION => ['pass', 'dribble', 'intercept', 'stamina'],
        TeamStyle::TACTICAL_COUNTER    => ['speed', 'shot', 'defense', 'tackle'],
        TeamStyle::TACTICAL_BALANCED   => ['attack', 'defense', 'pass', 'speed'],
    ];

    /**
     * Choisit le meilleur joueur pour l'équipe IA selon son style et ses besoins.
     */
    public function chooseBestPlayer(GameSave $gameSave, GameTeam $team): ?int
    {
        // Joueurs libres (hors fictifs : réservés aux sélections de Coupe du Monde,
        // et en respectant la config de visibilité : listes de joueurs actives/inactives)
        $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts')
            ->excludingFictional()
            ->visibleForConfig($gameSave)
            ->get();

        if ($freePlayers->isEmpty()) return null;

        // Effectif actuel
        $currentContracts = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $team->id)
            ->with('gamePlayer')
            ->get();

        $squadSize = $currentContracts->count();
        $budget    = DraftService::budgetFor($gameSave, $team);

        // Compter par poste
        $countByPos = ['GK' => 0, 'DEF' => 0, 'MID' => 0, 'ATT' => 0];
        foreach ($currentContracts as $c) {
            if (!$c->gamePlayer) continue;
            $g = $this->positionGroup($c->gamePlayer->position ?? '');
            if (isset($countByPos[$g])) $countByPos[$g]++;
        }

        // Déterminer le poste prioritaire
        $priorityPosition = $this->getPriorityPosition($countByPos, $squadSize);

        // Budget max par joueur (garder de quoi remplir l'effectif)
        $remaining    = max(1, DraftService::MIN_SQUAD - $squadSize);
        $seasonLength = $this->getSeasonLength($gameSave);
        $maxCostPerPlayer = (int) floor($budget / $remaining);

        // Filtrer les joueurs abordables
        // Même base que DraftService::executePick (coût majoré de la polyvalence) :
        // sinon l'IA choisit un joueur que le pick refusera.
        $draftService = app(DraftService::class);
        $costs = $freePlayers->mapWithKeys(fn ($p) => [$p->id => $draftService->pickCost($gameSave, $p->adjusted_cost, $seasonLength)]);
        $discountedCost = fn ($p) => $costs[$p->id];

        $affordable = $freePlayers->filter(fn($p) =>
            $discountedCost($p) <= $budget
        );

        if ($affordable->isEmpty()) return null;

        // Après le prochain choix, garder de quoi atteindre le minimum si le pool le permet.
        $cheapest = $affordable->sortBy($discountedCost)->take($remaining)->values();
        if ($cheapest->count() === $remaining) {
            $reservePlayers = $cheapest->take($remaining - 1)->keyBy('id');
            $reserve = $reservePlayers->sum($discountedCost);
            $replacementCost = $discountedCost($cheapest->last());
            $withinReserve = $affordable->filter(function ($player) use ($budget, $discountedCost, $reservePlayers, $reserve, $replacementCost) {
                $cost = $discountedCost($player);
                return $cost + $reserve + ($reservePlayers->has($player->id) ? $replacementCost - $cost : 0) <= $budget;
            });
            $affordable = $withinReserve->isNotEmpty() ? $withinReserve : $cheapest;
        }

        $philosophy    = $team->management_philosophy ?? 'balanced';
        $tacticalStyle = $team->tactical_style        ?? 'balanced';
        $byPosition = $affordable->groupBy(fn ($p) => $this->positionGroup($p->position ?? ''));
        $ratings = $affordable->mapWithKeys(fn ($p) => [$p->id => $this->playerOverall($p)]);
        $bestByPosition = $byPosition->map(fn ($players) => $players->max(fn ($p) => $ratings[$p->id]));
        $comparableByPosition = $byPosition->map(fn ($players, $pos) =>
            $players->filter(fn ($p) => $ratings[$p->id] >= $bestByPosition[$pos] - 4)->count()
        );
        $needKeeper = $countByPos['GK'] === 0;
        $keeperOptions = $comparableByPosition['GK'] ?? 0;
        $starOpening = $needKeeper && $squadSize < 2 && $keeperOptions >= 3 && $philosophy === TeamStyle::PHILOSOPHY_STARS;
        $teamAvg       = $this->avgOverall($currentContracts);

        // Duos d'alchimie : partenaires connus des candidats, pour valoriser
        // la reconstitution d'un duo (débloque le une-deux en match).
        $squadBaseIds = $currentContracts
            ->map(fn ($c) => $c->gamePlayer?->base_player_id)
            ->filter()->flip()->all();
        $poolBaseIds  = $freePlayers->pluck('base_player_id')->filter()->flip()->all();
        $partnerMap   = PlayerLink::partnerMapFor(
            array_keys($squadBaseIds + $poolBaseIds),
            withinSetOnly: false
        );

        $scored = $affordable->map(function ($player) use ($priorityPosition, $philosophy, $tacticalStyle, $teamAvg, $maxCostPerPlayer, $countByPos, $discountedCost, $partnerMap, $squadBaseIds, $poolBaseIds, $ratings, $bestByPosition, $comparableByPosition, $needKeeper, $keeperOptions, $starOpening, $squadSize) {
            $posGroup = $this->positionGroup($player->position ?? '');
            $overall  = $ratings[$player->id];
            $cost     = $discountedCost($player);
            $score    = 0.0;

            if ($posGroup === $priorityPosition) {
                $score += $starOpening ? 8 : 30;
            }
            if ($needKeeper && $posGroup === 'GK' && ($keeperOptions <= 2 || $squadSize >= 3)) {
                $score += 18;
            }

            // 2. Bonus poste en déficit (+15)
            $target  = self::TARGET_BY_POSITION[$posGroup] ?? 4;
            $current = $countByPos[$posGroup] ?? 0;
            if ($current < $target) {
                $score += 15;
                if ($overall >= $bestByPosition[$posGroup] - 4) {
                    $score += max(0, 4 - $comparableByPosition[$posGroup]) * 2;
                }
            }

            $score += $this->recruitmentReadiness($player, staminaWeight: 0.08);
            if ($starOpening && $posGroup !== 'GK') {
                $score += max(0, $overall - ($bestByPosition['GK'] ?? 0)) * 2;
            }

            // 3. Score selon philosophie de gestion
            $score += match ($philosophy) {
                TeamStyle::PHILOSOPHY_STARS      => $overall * 1.5,
                TeamStyle::PHILOSOPHY_COLLECTIVE => -abs($overall - $teamAvg) * 2 + $overall,
                TeamStyle::PHILOSOPHY_ECONOMIST  => $overall * 0.5 + min(25, 75 * $overall / max(100, $cost)),
                default                          => $overall + min(15, max(0, $overall - $teamAvg)) * 2,
            };

            // 4. Bonus style tactique (+10 par stat clé au-dessus de 50)
            $styleStats = self::STYLE_STATS[$tacticalStyle] ?? [];
            foreach ($styleStats as $stat) {
                $val = (int) ($player->{$stat} ?? 0);
                if ($val > 50) $score += 10;
                if ($val > 70) $score += 5;
            }

            // 5. Pénalité si trop cher par rapport au budget restant
            if ($cost > $maxCostPerPlayer * 2) {
                $score -= 20;
            }

            // 6. Duos d'alchimie : +25 si le partenaire est déjà dans l'effectif
            //    (une-deux débloqué immédiatement), +8 s'il est encore draftable
            //    (pari sur la reconstitution du duo).
            $duoBonus = 0;
            foreach ($partnerMap[$player->base_player_id] ?? [] as $link) {
                if (isset($squadBaseIds[$link['partner_id']])) {
                    $duoBonus = 25;
                    break;
                }
                if (isset($poolBaseIds[$link['partner_id']])) {
                    $duoBonus = max($duoBonus, 8);
                }
            }
            $score += $duoBonus;

            return ['player' => $player, 'score' => $score];
        });

        // Trier par score décroissant
        $sorted = $scored->sortByDesc('score');

        // Un peu de variété : 70% le meilleur, 25% le 2e, 5% le 3e
        $top = $sorted->values()->take(3);
        $roll = random_int(1, 100);

        // La variété ne doit pas faire choisir un profil très loin derrière.
        $top = $top->filter(fn ($entry) => $entry['score'] >= $top[0]['score'] - 12)->values();

        if ($roll <= 70 || $top->count() < 2) {
            return $top[0]['player']->id;
        } elseif ($roll <= 95 || $top->count() < 3) {
            return $top[1]['player']->id;
        } else {
            return $top[2]['player']->id;
        }
    }

    // ==========================
    //   HELPERS
    // ==========================

    protected function getPriorityPosition(array $countByPos, int $squadSize): string
    {
        // GK en priorité absolue si on n'en a aucun
        if (($countByPos['GK'] ?? 0) === 0) return 'GK';

        // Ensuite le poste le plus en déficit
        $deficits = [];
        foreach (self::TARGET_BY_POSITION as $pos => $target) {
            $deficits[$pos] = $target - ($countByPos[$pos] ?? 0);
        }

        arsort($deficits);
        $topPos = array_key_first($deficits);

        return $deficits[$topPos] > 0 ? $topPos : 'MID'; // fallback
    }

    protected function avgOverall($contracts): float
    {
        $players = $contracts->map(fn($c) => $c->gamePlayer)->filter();
        if ($players->isEmpty()) return 40;
        return $players->avg(fn($p) => $this->playerOverall($p));
    }

    protected function getSeasonLength(GameSave $gameSave): int
    {
        return SeasonService::lengthForTeamCount(GameTeam::where('game_save_id', $gameSave->id)->count());
    }

    /**
     * L'IA décide si elle veut continuer à drafter.
     * Retourne true si elle veut s'arrêter.
     */
    public function shouldFinishDraft(GameTeam $team, int $squadSize, int $budget): bool
    {
        if ($squadSize < DraftService::MIN_SQUAD) return false;
        if ($squadSize >= DraftService::MAX_SQUAD) return true;

        $philosophy = $team->management_philosophy ?? 'balanced';

        return match ($philosophy) {
            // Stars : concentre l’enveloppe sur un effectif resserré
            TeamStyle::PHILOSOPHY_STARS      => $squadSize >= 15 || $budget < 500,
            // Collectif : veut un effectif large
            TeamStyle::PHILOSOPHY_COLLECTIVE => $squadSize >= 17,
            // Économe : s’arrête dès le minimum
            TeamStyle::PHILOSOPHY_ECONOMIST  => $squadSize >= 14,
            // Équilibré : effectif raisonnable
            default                          => $squadSize >= 16 || $budget < 300,
        };
    }
}

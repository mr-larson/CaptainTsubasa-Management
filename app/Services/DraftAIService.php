<?php

namespace App\Services;

use App\Enums\TeamStyle;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;

class DraftAIService
{
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
        // Joueurs libres (hors fictifs : réservés aux sélections de Coupe du Monde)
        $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts')
            ->excludingFictional()
            ->get();

        if ($freePlayers->isEmpty()) return null;

        // Effectif actuel
        $currentContracts = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $team->id)
            ->with('gamePlayer')
            ->get();

        $squadSize = $currentContracts->count();
        $budget    = $team->budget ?? 0;

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
        $discountedCost = fn($p) => (int) floor(($p->cost ?? 0) * $seasonLength * DraftService::DRAFT_DISCOUNT);

        $affordable = $freePlayers->filter(fn($p) =>
            $discountedCost($p) <= $budget
        );

        if ($affordable->isEmpty()) {
            // Prendre le joueur gratuit le plus fort
            $freeAgents = $freePlayers->filter(fn($p) => ($p->cost ?? 0) === 0);
            if ($freeAgents->isEmpty()) return null;
            return $freeAgents->sortByDesc(fn($p) => $this->overallOf($p))->first()->id;
        }

        // Scorer chaque joueur
        $philosophy    = $team->management_philosophy ?? 'balanced';
        $tacticalStyle = $team->tactical_style        ?? 'balanced';
        $teamAvg       = $this->avgOverall($currentContracts);

        $scored = $affordable->map(function ($player) use ($priorityPosition, $philosophy, $tacticalStyle, $teamAvg, $maxCostPerPlayer, $countByPos, $discountedCost) {
            $posGroup = $this->positionGroup($player->position ?? '');
            $overall  = $this->overallOf($player);
            $cost     = $player->cost ?? 0;
            $score    = 0.0;

            // 1. Bonus poste prioritaire (+30)
            if ($posGroup === $priorityPosition) {
                $score += 30;
            }

            // 2. Bonus poste en déficit (+15)
            $target  = self::TARGET_BY_POSITION[$posGroup] ?? 4;
            $current = $countByPos[$posGroup] ?? 0;
            if ($current < $target) {
                $score += 15;
            }

            // 3. Score selon philosophie de gestion
            $score += match ($philosophy) {
                TeamStyle::PHILOSOPHY_STARS      => $overall * 1.5,
                TeamStyle::PHILOSOPHY_COLLECTIVE => -abs($overall - $teamAvg) * 2 + $overall,
                TeamStyle::PHILOSOPHY_ECONOMIST  => ($overall / max(1, $discountedCost($player))) * 50,
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

            return ['player' => $player, 'score' => $score];
        });

        // Trier par score décroissant
        $sorted = $scored->sortByDesc('score');

        // Un peu de variété : 70% le meilleur, 25% le 2e, 5% le 3e
        $top = $sorted->values()->take(3);
        $roll = random_int(1, 100);

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

    protected function positionGroup(string $position): string
    {
        $p = strtoupper(trim($position));
        if (str_contains($p, 'GK') || str_contains($p, 'GOAL'))    return 'GK';
        if (str_contains($p, 'DEF') || str_contains($p, 'BACK'))   return 'DEF';
        if (str_contains($p, 'MDF') || str_contains($p, 'MID') || str_contains($p, 'MOF')) return 'MID';
        if (str_contains($p, 'ATT') || str_contains($p, 'FOR'))    return 'ATT';
        return 'MID';
    }

    protected function overallOf($player): float
    {
        $keys = ['speed', 'stamina', 'attack', 'defense', 'shot', 'pass', 'dribble', 'block', 'intercept', 'tackle'];
        $vals = array_map(fn($k) => (int) ($player->{$k} ?? 0), $keys);
        $vals = array_filter($vals, fn($v) => $v > 0);
        return empty($vals) ? 0 : array_sum($vals) / count($vals);
    }

    protected function avgOverall($contracts): float
    {
        $players = $contracts->map(fn($c) => $c->gamePlayer)->filter();
        if ($players->isEmpty()) return 40;
        return $players->avg(fn($p) => $this->overallOf($p));
    }

    protected function getSeasonLength(GameSave $gameSave): int
    {
        $teamCount = GameTeam::where('game_save_id', $gameSave->id)->count();
        if ($teamCount < 2) return 28;
        return $teamCount % 2 === 1 ? $teamCount * 2 : ($teamCount - 1) * 2;
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
            // Stars : s'arrête tôt, garde du budget pour la saison
            TeamStyle::PHILOSOPHY_STARS      => $squadSize >= 15 || $budget < 500,
            // Collectif : veut un effectif large
            TeamStyle::PHILOSOPHY_COLLECTIVE => $squadSize >= 17,
            // Économe : s'arrête dès le minimum, préserve le budget
            TeamStyle::PHILOSOPHY_ECONOMIST  => $squadSize >= 14,
            // Équilibré : effectif raisonnable
            default                          => $squadSize >= 16 || $budget < 300,
        };
    }
}

<?php

namespace App\Services\WorldCup;

use App\Enums\PlayerPosition;
use App\Enums\TeamStyle;
use App\Helpers\FormationHelper;
use App\Models\Player;
use App\Models\PlayerLink;
use App\Services\Ai\AiLineUpService;
use App\Services\Ai\DraftAIService;
use Illuminate\Support\Collection;

/**
 * Convocation intelligente d'une sélection nationale pilotée par l'IA.
 *
 * Domaine : Coupe du Monde. Utilisé par NationalTeamAssembler (création de partie et aperçu).
 * Reprend la logique du draft de l'IA (DraftAIService) sans budget : on « drafte » l'effectif
 * joueur après joueur parmi tout le vivier de la nation, en tenant compte
 *   - des besoins par poste du schéma (un onze complet avant tout, puis la composition cible du banc,
 *     DraftAIService::TARGET_BY_POSITION) ;
 *   - de la philosophie de gestion de la nation (stars, collectif, équilibré…) ;
 *   - de son style tactique (stats clés, DraftAIService::STYLE_STATS) ;
 *   - des duos d'alchimie (PlayerLink) : un joueur dont le partenaire est déjà convoqué est favorisé.
 * Le choix est déterministe (pas de tirage), pour que l'aperçu et la partie donnent le même effectif.
 */
class NationalSquadPicker
{
    /** Taille de l'effectif convoqué. */
    public const SQUAD_SIZE = 18;

    /** Effectif minimum pour aligner un onze. */
    public const MIN_PLAYERS = 11;

    public function __construct(private AiLineUpService $lineups) {}

    /**
     * Compose l'effectif et la composition de départ d'une sélection.
     *
     * @param  Collection<int, Player>  $pool  vivier complet de la nation
     * @param  string|null  $formation  schéma favori de la nation (catalogue), sinon le mieux adapté
     * @return array{0: Collection<int, Player>, 1: array{formation: string, slots: array<int, int>}}|null
     *                                                                                                     [effectif (titulaires d'abord non garanti), composition], ou null si onze impossible ou sans gardien
     */
    public function pick(Collection $pool, ?string $formation, string $style = TeamStyle::TACTICAL_BALANCED, string $philosophy = TeamStyle::PHILOSOPHY_COLLECTIVE): ?array
    {
        if ($pool->count() < self::MIN_PLAYERS || ! $pool->contains(fn (Player $p) => $this->group($p) === 'GK')) {
            return null;
        }

        // Schéma retenu : le favori de la nation, sinon celui qui va le mieux au vivier.
        $shape = $this->lineupFor($pool, $formation)['formation'];
        $needs = $this->needsOf($shape);
        $targets = $this->targetsOf($needs);

        $partners = PlayerLink::partnerMapFor($pool->pluck('id')->all(), withinSetOnly: false);
        $poolIds = $pool->pluck('id')->flip()->all();

        // Meilleure note de chaque groupe de poste : sert d'étalon (un gardien à 70 et un attaquant à 90
        // ne se comparent pas), donc la qualité d'un joueur est mesurée relativement au meilleur de son poste.
        $bestByGroup = $pool->groupBy(fn (Player $p) => $this->group($p))
            ->map(fn (Collection $players) => $players->max(fn (Player $p) => $this->overall($p)));

        $squad = collect();
        $left = $pool->keyBy('id');
        while ($squad->count() < self::SQUAD_SIZE && $left->isNotEmpty()) {
            $counts = $squad->countBy(fn (Player $p) => $this->group($p));
            $squadIds = $squad->pluck('id')->flip()->all();
            // Qualité moyenne déjà convoquée par poste ; à défaut, le meilleur du poste (première prise = la meilleure).
            $avgByGroup = $squad->groupBy(fn (Player $p) => $this->group($p))
                ->map(fn (Collection $players) => $players->avg(fn (Player $p) => $this->quality($p, $bestByGroup)));

            $best = $left->sortBy('id')->map(fn (Player $p) => [
                'player' => $p,
                'score' => $this->score($p, $counts, $needs, $targets, $this->quality($p, $bestByGroup), $avgByGroup[$this->group($p)] ?? 100.0, $style, $philosophy, $partners[$p->id] ?? [], $squadIds, $poolIds),
            ])->sortByDesc('score')->first();

            $squad->push($best['player']);
            $left->forget($best['player']->id);
        }

        if ($squad->count() < self::MIN_PLAYERS) {
            return null;
        }

        $lineup = $this->lineupFor($squad, $formation);
        if (count($lineup['slots']) < self::MIN_PLAYERS) {
            return null;
        }

        return [$squad->values(), $lineup];
    }

    /**
     * Composition d'un effectif pour un schéma favori, ou pour le meilleur schéma s'il n'y en a pas.
     *
     * @return array{formation: string, slots: array<int, int>}
     */
    public function lineupFor(Collection $players, ?string $formation): array
    {
        return $formation
            ? $this->lineups->recommendLineupForFormation($players, $formation)
            : $this->lineups->recommendInitialLineup($players);
    }

    /** Note synthétique d'un joueur selon son poste (sert au tri et à l'affichage). */
    public function overall(Player $player): float
    {
        $s = $player->stats ?? [];
        $get = fn (string $k): float => (float) ($s[$k] ?? $player->{$k} ?? 0);

        return match ($this->group($player)) {
            'GK' => ($get('hand_save') + $get('punch_save') + $get('defense')) / 3,
            'DEF' => ($get('defense') + $get('tackle') + $get('block') + $get('intercept')) / 4,
            'MID' => ($get('pass') + $get('dribble') + $get('attack') + $get('speed')) / 4,
            default => ($get('shot') + $get('attack') + $get('dribble') + $get('speed')) / 4,
        };
    }

    /** Qualité d'un joueur sur 100 : sa note rapportée au meilleur joueur de son poste dans le vivier. */
    private function quality(Player $p, Collection $bestByGroup): float
    {
        return 100 - max(0, (float) $bestByGroup[$this->group($p)] - $this->overall($p));
    }

    /** Groupe de poste GK / DEF / MID / ATT (mêmes clés que le draft). */
    private function group(Player $player): string
    {
        return match ($player->position) {
            PlayerPosition::Goalkeeper => 'GK',
            PlayerPosition::Defender => 'DEF',
            PlayerPosition::Forward => 'ATT',
            default => 'MID',
        };
    }

    /**
     * Titulaires nécessaires par groupe de poste pour un schéma (zones : 0 GK, 1 DEF, 2-3 MID, 4 ATT).
     *
     * @return array<string, int>
     */
    private function needsOf(string $formation): array
    {
        $needs = ['GK' => 0, 'DEF' => 0, 'MID' => 0, 'ATT' => 0];
        foreach (FormationHelper::SLOT_ZONES[$formation] ?? FormationHelper::SLOT_ZONES[FormationHelper::DEFAULT_FORMATION] as $zone) {
            $needs[match ($zone) {
                0 => 'GK', 1 => 'DEF', 4 => 'ATT', default => 'MID'
            }]++;
        }

        return $needs;
    }

    /**
     * Effectif visé par groupe : le onze du schéma plus un remplaçant, sans descendre sous la
     * composition cible du draft (2 GK, 5 DEF, 5 MID, 4 ATT).
     *
     * @param  array<string, int>  $needs
     * @return array<string, int>
     */
    private function targetsOf(array $needs): array
    {
        $targets = [];
        foreach ($needs as $group => $need) {
            $targets[$group] = max($need + 1, DraftAIService::TARGET_BY_POSITION[$group] ?? 0);
        }

        return $targets;
    }

    /**
     * Score d'un candidat (même esprit que DraftAIService::chooseBestPlayer, sans coût).
     *
     * @param  float  $quality  qualité du candidat sur 100 (relative au meilleur de son poste)
     * @param  float  $avg  qualité moyenne déjà convoquée à son poste
     * @param  array<string, int>  $counts  convoqués par groupe
     * @param  array<int, array{partner_id:int}>  $partners  duos du candidat
     */
    private function score(Player $p, Collection $counts, array $needs, array $targets, float $quality, float $avg, string $style, string $philosophy, array $partners, array $squadIds, array $poolIds): float
    {
        $group = $this->group($p);
        $have = (int) ($counts[$group] ?? 0);
        $overall = $quality;
        $score = 0.0;

        // Un onze complet d'abord (gardien en tête), puis la composition cible du banc.
        if ($group === 'GK' && $have === 0) {
            $score += 60;
        }
        if ($have < $needs[$group]) {
            $score += 100; // le onze du schéma passe avant tout le reste
        } elseif ($have < $targets[$group]) {
            $score += 15;
        }

        $score += match ($philosophy) {
            TeamStyle::PHILOSOPHY_STARS => $overall * 1.5,
            TeamStyle::PHILOSOPHY_COLLECTIVE => -abs($overall - $avg) * 2 + $overall,
            TeamStyle::PHILOSOPHY_ECONOMIST => $overall * 0.5,
            default => $overall + min(15, max(0, $overall - $avg)) * 2,
        };

        foreach (DraftAIService::STYLE_STATS[$style] ?? [] as $stat) {
            $value = (int) ($p->stats[$stat] ?? $p->{$stat} ?? 0);
            if ($value > 50) {
                $score += 10;
            }
            if ($value > 70) {
                $score += 5;
            }
        }

        // Duos d'alchimie : +25 si le partenaire est déjà convoqué, +8 s'il est encore disponible.
        $duo = 0;
        foreach ($partners as $link) {
            if (isset($squadIds[$link['partner_id']])) {
                $duo = 25;
                break;
            }
            if (isset($poolIds[$link['partner_id']])) {
                $duo = max($duo, 8);
            }
        }

        return $score + $duo;
    }
}

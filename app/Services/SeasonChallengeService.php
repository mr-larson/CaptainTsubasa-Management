<?php

namespace App\Services;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Défis de saison : 5 objectifs tirés au sort pour CHAQUE équipe (humaine
 * comme IA) en début de saison, un par famille, de difficulté mixte
 * (2 faciles, 2 moyens, 1 difficile). Ils n'influencent pas le championnat :
 * ils rapportent une prime et alimentent un second classement de fin de saison.
 *
 * Tous les objectifs se mesurent après coup sur les données de la saison
 * (matchs, contrats, moral, budget, cartes) : l'IA n'a rien à « viser ».
 *
 * Donnée : GameSave->state['challenges'] = [
 *   'season' => int, 'drawn_at' => ISO, 'teams' => [teamId => [
 *       'baseline_budget' => int, 'objectives' => [...]]],
 * ]
 */
class SeasonChallengeService
{
    public const PER_SEASON = 5;

    /** Points (classement des défis) et prime (€) par niveau. */
    public const TIERS = [
        'easy'   => ['label' => 'Facile',    'points' => 1, 'reward' => 150, 'mult' => 0.6],
        'medium' => ['label' => 'Moyen',     'points' => 2, 'reward' => 300, 'mult' => 0.85],
        'hard'   => ['label' => 'Difficile', 'points' => 3, 'reward' => 500, 'mult' => 1.1],
    ];

    private const TIER_DRAW = ['easy', 'easy', 'medium', 'medium', 'hard'];

    /** Une famille = un objectif par saison. */
    public const FAMILIES = [
        'results'  => ['label' => 'Résultats',         'icon' => '🏆', 'keys' => ['rank_max', 'wins_min', 'unbeaten_streak', 'away_wins_min']],
        'goals'    => ['label' => 'Attaque & défense', 'icon' => '⚽', 'keys' => ['goals_for_min', 'goals_against_max', 'clean_sheets_min', 'top_scorer_min']],
        'squad'    => ['label' => 'Vestiaire',         'icon' => '🧑‍🤝‍🧑', 'keys' => ['avg_morale_min', 'signings_min']],
        'club'     => ['label' => 'Gestion',           'icon' => '💼', 'keys' => ['budget_min', 'cards_used_min']],
        'exploits' => ['label' => 'Exploits',          'icon' => '⚡', 'keys' => ['beat_stronger_min', 'big_wins_min']],
    ];

    /** Objectifs de type « au plus X » (échec dès que dépassé). */
    private const MAX_KEYS = ['goals_against_max'];

    /** Objectifs jugés sur l'état final (pas de validation anticipée). */
    private const FINAL_STATE_KEYS = ['rank_max', 'avg_morale_min', 'budget_min'];

    /** Modulation des seuils des équipes humaines selon le mandat. */
    private const DIFFICULTY_MULT = ['none' => 1.0, 'survival' => 0.9, 'standard' => 1.0, 'conquest' => 1.1];

    public function isEnabled(GameSave $gameSave): bool
    {
        return $gameSave->competition_type !== 'world_cup'
            && (bool) $gameSave->getConfig('season_challenges_enabled', true);
    }

    // ==========================
    //   TIRAGE
    // ==========================

    /**
     * Tire les défis de la saison courante pour toutes les équipes si ce n'est
     * pas déjà fait. Appelé à l'ouverture du dashboard (après la draft).
     */
    public function ensureSeasonChallenges(GameSave $gameSave): void
    {
        if (! $this->isEnabled($gameSave)) {
            return;
        }

        $season = (int) ($gameSave->season ?? 1);
        if ((int) ($gameSave->state['challenges']['season'] ?? 0) === $season) {
            return;
        }

        $strengths = app(CareerObjectiveService::class)->teamStrengths($gameSave);
        $teams     = GameTeam::where('game_save_id', $gameSave->id)->get()->keyBy('id');
        if ($strengths->isEmpty() || $strengths->count() < $teams->count()) {
            return; // effectifs pas encore montés : on réessaiera
        }

        $n            = $teams->count();
        $matches      = max(1, 2 * ($n - 1));
        $weeks        = SeasonService::lengthForTeamCount($n);
        $humanIncome  = WeekClosingService::humanIncomeModel($gameSave)['base'];
        $difficulty   = (string) $gameSave->getConfig('career_difficulty', 'standard');
        $rankedIds    = $strengths->keys()->values();

        $byTeam = [];
        foreach ($teams as $id => $team) {
            $expectedRank = $rankedIds->search($id) + 1;
            $ctx = [
                'n'             => $n,
                'matches'       => $matches,
                'weeks'         => $weeks,
                'expected_rank' => $expectedRank,
                's'             => $n > 1 ? 1 - ($expectedRank - 1) / ($n - 1) : 0.5,
                'budget'        => (int) ($team->budget ?? 0),
                'income'        => $team->is_controlled ? $humanIncome : WeekClosingService::BASE_INCOME,
                'diff_mult'     => $team->is_controlled ? (self::DIFFICULTY_MULT[$difficulty] ?? 1.0) : 1.0,
            ];

            $byTeam[$id] = [
                'baseline_budget' => $ctx['budget'],
                'expected_rank'   => $expectedRank,
                'objectives'      => $this->drawFor($gameSave, (int) $id, $season, $ctx),
            ];
        }

        // Compteur de recrues remis à zéro avant d'ouvrir la saison des défis.
        GameTeam::where('game_save_id', $gameSave->id)->update(['season_signings' => 0]);

        $state = $gameSave->state ?? [];
        $state['challenges'] = [
            'season'   => $season,
            'drawn_at' => now()->toIso8601String(),
            'teams'    => $byTeam,
        ];
        $gameSave->state = $state;
        $gameSave->save();
    }

    /** @return list<array<string, mixed>> */
    private function drawFor(GameSave $gameSave, int $teamId, int $season, array $ctx): array
    {
        // Tirage reproductible : même save + équipe + saison → mêmes défis.
        $rng = new Randomizer(new Mt19937(crc32("challenges:{$gameSave->id}:{$teamId}:{$season}")));

        $tiers = $rng->shuffleArray(self::TIER_DRAW);
        $out   = [];
        $i     = 0;
        foreach (self::FAMILIES as $family => $def) {
            $keys = array_values(array_filter($def['keys'], fn ($k) => $this->isEligible($k, $ctx)));
            $key  = $keys[$rng->getInt(0, count($keys) - 1)];
            $tier = $tiers[$i++];

            $target = $this->target($key, $tier, $ctx);
            $out[] = [
                'key'    => $key,
                'family' => $family,
                'tier'   => $tier,
                'target' => $target,
                'label'  => $this->label($key, $target),
                'points' => self::TIERS[$tier]['points'],
                'reward' => self::TIERS[$tier]['reward'],
            ];
        }

        return $out;
    }

    private function isEligible(string $key, array $ctx): bool
    {
        // Le favori n'a personne de plus fort à battre.
        return ! ($key === 'beat_stronger_min' && $ctx['expected_rank'] <= 1);
    }

    private function target(string $key, string $tier, array $ctx): int
    {
        $m    = $ctx['matches'];
        $s    = $ctx['s'];
        $mult = self::TIERS[$tier]['mult'] * $ctx['diff_mult'];
        $atLeast = fn (float $v, int $min = 1) => max($min, (int) round($v * $mult));

        return match ($key) {
            'rank_max'          => max(1, min(max(1, $ctx['n'] - 1), $ctx['expected_rank'] + ['easy' => 2, 'medium' => 0, 'hard' => -1][$tier])),
            'wins_min'          => $atLeast($m * (0.15 + 0.45 * $s)),
            'unbeaten_streak'   => $atLeast(2 + 4 * $s, 2),
            'away_wins_min'     => $atLeast(($m / 2) * (0.1 + 0.4 * $s)),
            'goals_for_min'     => $atLeast($m * (1.0 + 1.2 * $s)),
            'goals_against_max' => max(0, (int) round($m * (2.6 - 1.4 * $s) / $mult)),
            'clean_sheets_min'  => $atLeast($m * (0.08 + 0.3 * $s)),
            'top_scorer_min'    => $atLeast($m * (1.0 + 1.2 * $s) * 0.25, 2),
            'avg_morale_min'    => min(90, (int) round(['easy' => 55, 'medium' => 60, 'hard' => 66][$tier] * $ctx['diff_mult'])),
            'signings_min'      => ['easy' => 1, 'medium' => 2, 'hard' => 3][$tier],
            'budget_min'        => $ctx['budget'] + (int) (round($ctx['income'] * $ctx['weeks'] * ['easy' => 0.2, 'medium' => 0.4, 'hard' => 0.6][$tier] * $ctx['diff_mult'] / 50) * 50),
            'cards_used_min'    => ['easy' => 2, 'medium' => 4, 'hard' => 6][$tier],
            'beat_stronger_min' => min(6, $atLeast(2 * ($ctx['expected_rank'] - 1) * 0.15)),
            'big_wins_min'      => $atLeast($m * (0.03 + 0.12 * $s)),
        };
    }

    private function label(string $key, int $t): string
    {
        return match ($key) {
            'rank_max'          => $t <= 1 ? 'Finir champion' : "Finir {$t}e ou mieux",
            'wins_min'          => "Gagner {$t} matchs",
            'unbeaten_streak'   => "Enchaîner {$t} matchs sans défaite",
            'away_wins_min'     => "Gagner {$t} match(s) à l'extérieur",
            'goals_for_min'     => "Marquer {$t} buts",
            'goals_against_max' => "Encaisser au plus {$t} buts",
            'clean_sheets_min'  => "Garder sa cage inviolée {$t} fois",
            'top_scorer_min'    => "Avoir un buteur à {$t} buts",
            'avg_morale_min'    => "Moral moyen de l'effectif ≥ {$t} en fin de saison",
            'signings_min'      => "Recruter {$t} joueur(s) en cours de saison",
            'budget_min'        => "Finir avec au moins {$t} € en caisse (avant primes)",
            'cards_used_min'    => "Jouer {$t} cartes",
            'beat_stronger_min' => "Battre {$t} fois une équipe réputée plus forte",
            'big_wins_min'      => "Gagner {$t} match(s) par 3 buts d'écart ou plus",
        };
    }

    // ==========================
    //   MESURE
    // ==========================

    /**
     * Valeur courante de chaque objectif de l'équipe.
     *
     * @param  array<string, mixed>  $entry  state['challenges']['teams'][id]
     * @return array<string, int>  key => valeur
     */
    private function measure(GameSave $gameSave, GameTeam $team, array $entry, ?int $rank, array $strengthRanks): array
    {
        $teamId = (int) $team->id;
        $keys   = array_column($entry['objectives'] ?? [], 'key');
        $values = [];

        $needsMatches = array_intersect($keys, ['wins_min', 'unbeaten_streak', 'away_wins_min', 'clean_sheets_min', 'beat_stronger_min', 'big_wins_min']);
        $games = $needsMatches
            ? GameMatch::where('game_save_id', $gameSave->id)
                ->where('status', 'played')
                ->where(fn ($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
                ->orderBy('week')
                ->get(['home_team_id', 'away_team_id', 'home_score', 'away_score'])
                ->map(function (GameMatch $m) use ($teamId) {
                    $home = (int) $m->home_team_id === $teamId;

                    return [
                        'home' => $home,
                        'gf'   => (int) ($home ? $m->home_score : $m->away_score),
                        'ga'   => (int) ($home ? $m->away_score : $m->home_score),
                        'opp'  => (int) ($home ? $m->away_team_id : $m->home_team_id),
                    ];
                })
            : collect();

        foreach ($keys as $key) {
            $values[$key] = match ($key) {
                'rank_max'          => (int) ($rank ?? 99),
                'wins_min'          => $games->filter(fn ($g) => $g['gf'] > $g['ga'])->count(),
                'unbeaten_streak'   => $this->bestUnbeatenStreak($games),
                'away_wins_min'     => $games->filter(fn ($g) => ! $g['home'] && $g['gf'] > $g['ga'])->count(),
                'goals_for_min'     => (int) ($team->goals_for ?? 0),
                'goals_against_max' => (int) ($team->goals_against ?? 0),
                'clean_sheets_min'  => $games->filter(fn ($g) => $g['ga'] === 0)->count(),
                'top_scorer_min'    => $this->topScorerGoals($gameSave, $teamId),
                'avg_morale_min'    => $this->averageMorale($gameSave, $teamId),
                'signings_min'      => (int) ($team->season_signings ?? 0),
                'budget_min'        => (int) ($team->budget ?? 0),
                'cards_used_min'    => GameBonusCard::where('game_save_id', $gameSave->id)
                    ->where('game_team_id', $teamId)
                    ->where('status', 'used')
                    ->where('used_season', (int) ($gameSave->season ?? 1))
                    ->count(),
                'beat_stronger_min' => $games->filter(fn ($g) => $g['gf'] > $g['ga']
                    && isset($strengthRanks[$g['opp']], $strengthRanks[$teamId])
                    && $strengthRanks[$g['opp']] < $strengthRanks[$teamId])->count(),
                'big_wins_min'      => $games->filter(fn ($g) => $g['gf'] - $g['ga'] >= 3)->count(),
                default             => 0,
            };
        }

        return $values;
    }

    private function bestUnbeatenStreak(Collection $games): int
    {
        $best = $cur = 0;
        foreach ($games as $g) {
            $cur  = $g['gf'] >= $g['ga'] ? $cur + 1 : 0;
            $best = max($best, $cur);
        }

        return $best;
    }

    private function teamPlayerIds(GameSave $gameSave, int $teamId): Collection
    {
        return GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $teamId)
            ->activeAt((int) ($gameSave->week ?? 1))
            ->pluck('game_player_id');
    }

    /**
     * Meilleur total de buts d'un joueur SOUS LES COULEURS du club : buts
     * attribués match par match d'après la composition enregistrée
     * (match_stats.lineups). Un buteur parti garde ses buts pour le club ;
     * une recrue n'apporte pas ceux marqués ailleurs.
     */
    private function topScorerGoals(GameSave $gameSave, int $teamId): int
    {
        $goals = [];

        GameMatch::where('game_save_id', $gameSave->id)
            ->where('status', 'played')
            ->whereNotNull('match_stats')
            ->where(fn ($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->get(['home_team_id', 'match_stats'])
            ->each(function (GameMatch $m) use ($teamId, &$goals) {
                $side   = (int) $m->home_team_id === $teamId ? 'home' : 'away';
                $squad  = array_map('intval', array_column($m->match_stats['lineups'][$side]['players'] ?? [], 'id'));
                if (! $squad) {
                    return; // ancien match sans composition : attribution impossible
                }
                foreach ($m->match_stats['players'] ?? [] as $pid => $p) {
                    if (in_array((int) $pid, $squad, true)) {
                        $goals[(int) $pid] = ($goals[(int) $pid] ?? 0) + (int) ($p['offense']['goals'] ?? 0);
                    }
                }
            });

        return $goals ? max($goals) : 0;
    }

    private function averageMorale(GameSave $gameSave, int $teamId): int
    {
        $ids = $this->teamPlayerIds($gameSave, $teamId);
        if ($ids->isEmpty()) {
            return 0;
        }

        return (int) round(GamePlayer::whereIn('id', $ids)->avg('morale') ?? 0);
    }

    /**
     * Comptabilise une recrue si les défis de la saison courante sont tirés
     * (la draft de début de saison a lieu avant le tirage : elle ne compte pas).
     */
    public static function recordSigning(GameContract $contract): void
    {
        $save = GameSave::query()->select(['id', 'season', 'competition_type', 'state'])->find($contract->game_save_id);
        if (! $save || (int) ($save->state['challenges']['season'] ?? 0) !== (int) ($save->season ?? 1)) {
            return;
        }

        GameTeam::whereKey($contract->game_team_id)->increment('season_signings');
    }

    /**
     * Statut d'un objectif : done / failed (définitifs) ou on_track / off_track
     * (encore ouverts). $final = fin de saison : tout devient done / failed.
     */
    private function status(string $key, int $value, int $target, bool $final): string
    {
        if (in_array($key, self::MAX_KEYS, true)) {
            if ($value > $target) return 'failed';
            return $final ? 'done' : 'on_track';
        }

        if ($key === 'rank_max') {
            $ok = $value <= $target;
        } else {
            $ok = $value >= $target;
        }

        if (in_array($key, self::FINAL_STATE_KEYS, true)) {
            return $final ? ($ok ? 'done' : 'failed') : ($ok ? 'on_track' : 'off_track');
        }

        return $ok ? 'done' : ($final ? 'failed' : 'off_track');
    }

    /** @return array<int, int> teamId => rang de force (1 = plus fort) */
    private function strengthRanks(GameSave $gameSave): array
    {
        $out = [];
        foreach ($gameSave->state['challenges']['teams'] ?? [] as $id => $entry) {
            $out[(int) $id] = (int) ($entry['expected_rank'] ?? 99);
        }

        return $out;
    }

    /**
     * @param  Collection<int, GameTeam>  $standings
     * @return list<array<string, mixed>>
     */
    private function evaluateTeam(GameSave $gameSave, GameTeam $team, array $entry, ?int $rank, array $strengthRanks, bool $final): array
    {
        $values = $this->measure($gameSave, $team, $entry, $rank, $strengthRanks);

        return array_map(function (array $o) use ($values, $final) {
            $value = $values[$o['key']] ?? 0;

            return $o + [
                'value'        => $value,
                'status'       => $this->status($o['key'], $value, (int) $o['target'], $final),
                'family_label' => self::FAMILIES[$o['family']]['label'] ?? $o['family'],
                'family_icon'  => self::FAMILIES[$o['family']]['icon'] ?? '🎯',
                'tier_label'   => self::TIERS[$o['tier']]['label'] ?? $o['tier'],
            ];
        }, $entry['objectives'] ?? []);
    }

    /** Rangs courants du championnat, avec le même tri que SeasonService. */
    private function currentRanks(GameSave $gameSave): array
    {
        $teams = GameTeam::where('game_save_id', $gameSave->id)->get();
        $sorted = app(SeasonService::class)->sortStandings($teams);

        $ranks = [];
        foreach ($sorted->values() as $i => $t) {
            $ranks[(int) $t->id] = $i + 1;
        }

        return $ranks;
    }

    // ==========================
    //   PRÉSENTATION & FIN DE SAISON
    // ==========================

    /**
     * Défis de l'équipe active pour le dashboard (progression en direct).
     *
     * @return array<string, mixed>|null
     */
    public function presentation(GameSave $gameSave): ?array
    {
        $data = $gameSave->state['challenges'] ?? null;
        if (! $this->isEnabled($gameSave) || ! $data || (int) $data['season'] !== (int) ($gameSave->season ?? 1)) {
            return null;
        }

        $teamId = (int) ($gameSave->controlled_game_team_id ?? 0);
        $entry  = $data['teams'][$teamId] ?? $data['teams'][(string) $teamId] ?? null;
        $team   = $teamId ? GameTeam::find($teamId) : null;
        if (! $entry || ! $team) {
            return null;
        }

        $ranks      = $this->currentRanks($gameSave);
        $objectives = $this->evaluateTeam($gameSave, $team, $entry, $ranks[$teamId] ?? null, $this->strengthRanks($gameSave), false);

        return [
            'season'         => (int) $data['season'],
            'objectives'     => $objectives,
            'points_secured' => array_sum(array_map(fn ($o) => $o['status'] === 'done' ? $o['points'] : 0, $objectives)),
            'points_max'     => array_sum(array_column($objectives, 'points')),
        ];
    }

    /**
     * Fin de saison : évalue les défis de toutes les équipes, verse les primes
     * et renvoie le classement des défis (trié par points, puis nombre de défis
     * réussis, puis rang en championnat). Le détail n'est gardé que pour les
     * équipes humaines (le state reste compact).
     *
     * À appeler AVANT le versement des primes de classement (objectif budget).
     *
     * @param  Collection<int, GameTeam>  $standings  équipes triées (1er en tête)
     * @return list<array<string, mixed>>|null
     */
    public function evaluateSeasonEnd(GameSave $gameSave, Collection $standings): ?array
    {
        $data = $gameSave->state['challenges'] ?? null;
        if (! $this->isEnabled($gameSave) || ! $data || (int) $data['season'] !== (int) ($gameSave->season ?? 1)) {
            return null;
        }

        $strengthRanks = $this->strengthRanks($gameSave);
        $rows = [];

        foreach ($standings->values() as $i => $team) {
            $entry = $data['teams'][$team->id] ?? $data['teams'][(string) $team->id] ?? null;
            if (! $entry) {
                continue;
            }

            $objectives = $this->evaluateTeam($gameSave, $team, $entry, $i + 1, $strengthRanks, true);
            $achieved   = array_filter($objectives, fn ($o) => $o['status'] === 'done');
            $reward     = array_sum(array_column($achieved, 'reward'));

            $rows[] = [
                'team_id'       => (int) $team->id,
                'name'          => $team->name,
                'logo_path'     => $team->logo_path,
                'is_controlled' => (bool) $team->is_controlled,
                'league_rank'   => $i + 1,
                'points'        => array_sum(array_column($achieved, 'points')),
                'achieved'      => count($achieved),
                'total'         => count($objectives),
                'reward'        => $reward,
                'objectives'    => $team->is_controlled ? array_values($objectives) : null,
            ];

            if ($reward > 0) {
                $team->budget = (int) ($team->budget ?? 0) + $reward;
                $team->save();
            }
        }

        usort($rows, fn ($a, $b) => [$b['points'], $b['achieved'], $a['league_rank']]
            <=> [$a['points'], $a['achieved'], $b['league_rank']]);

        foreach ($rows as $i => &$row) {
            $row['rank'] = $i + 1;
        }
        unset($row);

        return $rows;
    }
}

<?php

namespace App\Services\Season;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;

/**
 * Boucle de motivation « carrière » du mode Ligue : la direction fixe un objectif
 * de classement par saison, suit une jauge de confiance qui évolue match après
 * match, et tranche en fin de saison (maintien, prime, licenciement). Un objectif
 * long terme (gagner N titres) conclut la partie par une victoire de carrière.
 *
 * Toute la donnée vit dans GameSave->gameState()->career(). Le mode Coupe du Monde
 * (élimination directe, fin naturelle) n'est pas concerné.
 */
class CareerObjectiveService
{
    /**
     * Exigence du board par niveau de mandat choisi à la création (objectifs, confiance,
     * victoire) : rang cible, progression de force, moral, budget. Le volet économie (budget de départ, revenus) est dans GameConfig::ECONOMY_PRESETS.
     */
    public const PRESETS = [
        'survival' => [
            'label'         => 'Survie',
            'confidence'    => 45,
            'titles'        => 1,
            'seasons'       => 3,    // saisons réussies (score ≥ seuil) pour gagner la carrière
            'rank_offset'   => 2,   // objectif plus indulgent (rang plus bas toléré)
            'win_bonus'     => 20,
            'fail_penalty'  => 16,
            'strength_gain' => 1,    // progression de force moyenne du onze exigée sur la saison
            'morale_target' => 55,   // moral moyen visé en fin de saison
            'budget_share'  => 0.2,   // part des revenus de la saison à mettre de côté
            'rank_weight'   => 30,    // poids du classement dans le score (les 3 autres piliers se partagent le reste)
            'success_threshold' => 50,  // score minimal (0-100) pour que la saison compte comme réussie
        ],
        'standard' => [
            'label'         => 'Standard',
            'confidence'    => 50,
            'titles'        => 2,
            'seasons'       => 4,    // saisons réussies (score ≥ seuil) pour gagner la carrière
            'rank_offset'   => 0,
            'win_bonus'     => 18,
            'fail_penalty'  => 22,
            'strength_gain' => 2,    // progression de force moyenne du onze exigée sur la saison
            'morale_target' => 60,   // moral moyen visé en fin de saison
            'budget_share'  => 0.4,   // part des revenus de la saison à mettre de côté
            'rank_weight'   => 40,    // poids du classement dans le score (les 3 autres piliers se partagent le reste)
            'success_threshold' => 60,  // score minimal (0-100) pour que la saison compte comme réussie
        ],
        'conquest' => [
            'label'         => 'Conquête',
            'confidence'    => 55,
            'titles'        => 3,
            'seasons'       => 5,    // saisons réussies (score ≥ seuil) pour gagner la carrière
            'rank_offset'   => -2,  // objectif exigeant (doit finir plus haut)
            'win_bonus'     => 15,
            'fail_penalty'  => 28,
            'strength_gain' => 3,    // progression de force moyenne du onze exigée sur la saison
            'morale_target' => 66,   // moral moyen visé en fin de saison
            'budget_share'  => 0.6,   // part des revenus de la saison à mettre de côté
            'rank_weight'   => 50,    // poids du classement dans le score (les 3 autres piliers se partagent le reste)
            'success_threshold' => 70,  // score minimal (0-100) pour que la saison compte comme réussie
        ],
    ];

    /** Seuils de la jauge de confiance (0–100). */
    public const CONFIDENCE_MAX     = 100;
    public const CONFIDENCE_ALERT   = 25;   // board en alerte sous ce seuil
    public const CONFIDENCE_FIRED   = 0;    // licenciement à 0

    /** Mouvements de confiance par résultat de match. */
    private const MATCH_WIN  = 5;
    private const MATCH_DRAW = 1;
    private const MATCH_LOSS = -5;

    /** Indique si la boucle de carrière est active : désactivée en Coupe du Monde et quand la difficulté de carrière est 'none'. */
    public function isEnabled(GameSave $gameSave): bool
    {
        if ($gameSave->isWorldCup()) {
            return false;
        }

        return ($gameSave->getConfig('career_difficulty', 'standard') ?? 'none') !== 'none';
    }

    /**
     * Données carrière normalisées (avec valeurs par défaut). Lecture seule :
     * pour modifier, repasser le tableau à persist().
     *
     * @return array<string, mixed>
     */
    public function data(GameSave $gameSave): array
    {
        $career = $gameSave->gameState()->career();
        $preset = $this->preset($gameSave);

        return array_merge([
            'difficulty'      => $gameSave->getConfig('career_difficulty', 'standard'),
            'confidence'      => $preset['confidence'],
            'status'          => 'active',  // active | fired | won
            'mandate'         => null,
            'titles_required' => $preset['titles'],
            'titles_won'      => 0,
            // Victoire : 'seasons' (N saisons réussies, mandat à piliers) ou 'titles' (parties plus anciennes : N titres).
            'win_mode'        => 'titles',
            'seasons_required' => $preset['seasons'],
            'seasons_won'     => 0,
            'history'         => [],
            'last_verdict'    => null,
            'fired_reason'    => null,
        ], $career);
    }

    /**
     * Présentation pour le dashboard (jauge + objectif courant + palmarès).
     *
     * @return array<string, mixed>|null
     */
    public function presentation(GameSave $gameSave): ?array
    {
        if (! $this->isEnabled($gameSave)) {
            return null;
        }

        $c = $this->data($gameSave);

        return [
            'difficulty'      => $c['difficulty'],
            'difficulty_label' => $this->preset($gameSave)['label'],
            'confidence'      => (int) $c['confidence'],
            'alert'           => (int) $c['confidence'] <= self::CONFIDENCE_ALERT,
            'status'          => $c['status'],
            'mandate'         => $c['mandate'],
            'titles_required' => (int) $c['titles_required'],
            'titles_won'      => (int) $c['titles_won'],
            // Valeurs actuelles des piliers mesurables en cours de saison (classement : voir le dashboard).
            'live'            => ! empty($c['mandate']['pillars']) ? $this->livePillarValues($gameSave) : null,
            'win_mode'        => $c['win_mode'],
            'seasons_required' => (int) $c['seasons_required'],
            'seasons_won'     => (int) $c['seasons_won'],
            'last_verdict'    => $c['last_verdict'],
            'fired_reason'    => $c['fired_reason'],
            // Historique des saisons jouées, trié par n° de saison croissant.
            'history'         => collect($c['history'] ?? [])
                ->sortBy('season')
                ->values()
                ->all(),
        ];
    }

    /**
     * Garantit qu'un mandat existe pour la saison courante. Appelé à l'ouverture
     * du dashboard : couvre aussi bien le mode pré-fait que le mode draft (où
     * l'effectif n'existe qu'une fois la draft terminée).
     */
    public function ensureSeasonMandate(GameSave $gameSave): void
    {
        if (! $this->isEnabled($gameSave)) {
            return;
        }

        $c = $this->data($gameSave);

        if ($c['status'] !== 'active') {
            return;
        }

        $season = (int) ($gameSave->season ?? 1);
        if (($c['mandate']['season'] ?? null) === $season) {
            return; // mandat déjà fixé pour cette saison
        }

        $strengths = $this->teamStrengths($gameSave);
        if ($strengths->isEmpty()) {
            return; // effectifs pas encore montés (draft en cours) : on réessaiera
        }

        $controlledId = (int) ($gameSave->controlled_game_team_id ?? 0);
        if (! $controlledId || ! $strengths->has($controlledId)) {
            return;
        }

        $teamCount    = $strengths->count();
        $expectedRank = $strengths->keys()->search($controlledId) + 1; // 1 = plus fort
        $preset       = $this->preset($gameSave);

        $targetRank = max(1, min($teamCount, $expectedRank + $preset['rank_offset']));

        $c['win_mode'] = 'seasons';   // un mandat à piliers bascule la partie sur « N saisons réussies »
        $c['mandate'] = [
            'season'        => $season,
            'target_rank'   => $targetRank,
            'expected_rank' => $expectedRank,
            'team_count'    => $teamCount,
            'label'         => $targetRank <= 1
                ? 'Titre de champion'
                : "Finir dans le top {$targetRank}",
            'pillars'       => $this->buildPillars($gameSave, $preset, $strengths, $controlledId, $targetRank, $expectedRank, $teamCount),
        ];

        $this->persist($gameSave, $c);
    }

    /**
     * Met à jour la confiance d'après le résultat du match de l'équipe contrôlée
     * pour la semaine écoulée. Peut déclencher un licenciement en cours de saison
     * (confiance tombée à 0).
     */
    public function afterWeek(GameSave $gameSave, int $week): void
    {
        if (! $this->isEnabled($gameSave)) {
            return;
        }

        $c = $this->data($gameSave);
        if ($c['status'] !== 'active' || ! $c['mandate']) {
            return;
        }

        $controlledId = (int) ($gameSave->controlled_game_team_id ?? 0);
        if (! $controlledId) {
            return;
        }

        $match = GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'played')
            ->where(fn ($q) => $q->where('home_team_id', $controlledId)
                ->orWhere('away_team_id', $controlledId))
            ->first();

        if (! $match) {
            return; // semaine de repos : pas de match pour cette équipe
        }

        $isHome = (int) $match->home_team_id === $controlledId;
        $gf = (int) ($isHome ? $match->home_score : $match->away_score);
        $ga = (int) ($isHome ? $match->away_score : $match->home_score);

        $delta = $gf > $ga ? self::MATCH_WIN : ($gf < $ga ? self::MATCH_LOSS : self::MATCH_DRAW);

        // Modulateur selon la force de l'adversaire : battre plus fort rapporte plus,
        // perdre contre plus faible coûte plus.
        $strengths = $this->teamStrengths($gameSave);
        $oppId     = $isHome ? (int) $match->away_team_id : (int) $match->home_team_id;
        if ($strengths->has($controlledId) && $strengths->has($oppId)) {
            $myRank  = $strengths->keys()->search($controlledId);
            $oppRank = $strengths->keys()->search($oppId);
            $oppStronger = $oppRank < $myRank; // rang plus petit = plus fort

            if ($gf > $ga && $oppStronger)  $delta += 3;  // exploit
            if ($gf < $ga && ! $oppStronger) $delta -= 3; // contre-performance
        }

        $c['confidence'] = $this->clampConfidence((int) $c['confidence'] + $delta);

        if ($c['confidence'] <= self::CONFIDENCE_FIRED) {
            $c['status']       = 'fired';
            $c['fired_reason'] = 'mid_season';
        }

        $this->persist($gameSave, $c);
    }

    /**
     * Verdict de fin de saison : compare le classement final au mandat, ajuste la
     * confiance, comptabilise les titres et décide du maintien, du licenciement ou
     * de la victoire de carrière. Renvoie le verdict pour l'écran de fin de saison.
     *
     * @param  Collection<int, GameTeam>  $standings  équipes triées (1er en tête)
     * @return array<string, mixed>|null
     */
    public function evaluateSeasonEnd(GameSave $gameSave, Collection $standings): ?array
    {
        if (! $this->isEnabled($gameSave)) {
            return null;
        }

        $c = $this->data($gameSave);
        if ($c['status'] !== 'active') {
            return $c['last_verdict'] ?? null;
        }

        $controlledId = (int) ($gameSave->controlled_game_team_id ?? 0);
        $rank = $standings->search(fn ($t) => (int) $t->id === $controlledId);
        if ($rank === false) {
            return null;
        }
        $rank += 1;

        $mandate    = $c['mandate'] ?? ['target_rank' => $rank, 'label' => '—'];
        $target     = (int) ($mandate['target_rank'] ?? $rank);
        $preset     = $this->preset($gameSave);
        $isChampion = $rank === 1;

        $score = null;
        $pillars = null;
        if (! empty($mandate['pillars'])) {
            // Mandat à 4 piliers : le score (0-100) décide du mouvement de confiance.
            [$pillars, $score] = $this->scorePillars($gameSave, $mandate['pillars'], $rank, $controlledId, $preset);
            $met = $score >= $preset['success_threshold'];

            if ($met) {
                $delta = $preset['win_bonus']
                    + (int) round(8 * ($score - $preset['success_threshold']) / max(1, 100 - $preset['success_threshold']))
                    + ($isChampion ? 4 : 0);
            } else {
                $delta = - (int) round($preset['fail_penalty'] * (1 + ($preset['success_threshold'] - $score) / $preset['success_threshold']));
            }
        } else {
            // Ancien mandat (partie commencée avant les piliers) : classement seul.
            $met = $rank <= $target;

            if ($met) {
                // Dépassement de l'objectif : bonus supplémentaire par rang gagné.
                $delta = $preset['win_bonus'] + max(0, $target - $rank) * 4;
            } else {
                // Échec : pénalité aggravée selon l'ampleur du raté.
                $delta = - (int) round($preset['fail_penalty'] * (1 + 0.25 * ($rank - $target)));
            }
        }

        $c['confidence'] = $this->clampConfidence((int) $c['confidence'] + $delta);

        if ($isChampion) {
            $c['titles_won'] = (int) $c['titles_won'] + 1;
        }
        if ($pillars !== null && $met) {
            $c['seasons_won'] = (int) $c['seasons_won'] + 1;
        }

        // Décision : victoire de carrière > licenciement > maintien.
        $outcome = 'retained';
        $victory = $c['win_mode'] === 'seasons'
            ? (int) $c['seasons_won'] >= (int) $c['seasons_required']
            : (int) $c['titles_won'] >= (int) $c['titles_required'];
        if ($victory) {
            $c['status'] = 'won';
            $outcome = 'won';
        } elseif ($c['confidence'] <= self::CONFIDENCE_FIRED) {
            $c['status']       = 'fired';
            $c['fired_reason'] = 'season_end';
            $outcome = 'fired';
        }

        $verdict = [
            'season'      => (int) ($gameSave->season ?? 1),
            'rank'        => $rank,
            'target_rank' => $target,
            'mandate'     => $mandate['label'] ?? null,
            'met'         => $met,
            'score'       => $score,
            'pillars'     => $pillars,
            'champion'    => $isChampion,
            'confidence'  => (int) $c['confidence'],
            'delta'       => $delta,
            'outcome'     => $outcome,
            'titles_won'  => (int) $c['titles_won'],
            'titles_required' => (int) $c['titles_required'],
            'seasons_won'     => (int) $c['seasons_won'],
            'seasons_required' => (int) $c['seasons_required'],
        ];

        $c['last_verdict'] = $verdict;
        $c['history'][(string) $verdict['season']] = $verdict;

        $this->persist($gameSave, $c);

        return $verdict;
    }

    /** Réinitialise le mandat pour qu'un nouveau soit généré à la saison suivante. */
    public function resetMandateForNewSeason(GameSave $gameSave): void
    {
        if (! $this->isEnabled($gameSave)) {
            return;
        }

        $c = $this->data($gameSave);
        $c['mandate'] = null;
        $this->persist($gameSave, $c);
    }

    /** Vrai si le joueur a été licencié ou a gagné sa carrière (la partie est terminée). */
    public function isGameOver(GameSave $gameSave): bool
    {
        if (! $this->isEnabled($gameSave)) {
            return false;
        }

        return in_array($this->data($gameSave)['status'], ['fired', 'won'], true);
    }

    // ==========================
    //   HELPERS PRIVÉS
    // ==========================

    /**
     * Mesure les 4 piliers en fin de saison et calcule le score du mandat (0-100) :
     * chaque pilier atteint rapporte son poids (classement : rank_weight, les trois
     * autres se partagent le reste à parts égales).
     *
     * @param  array<string, array<string, int|float>>  $targets  piliers fixés dans le mandat
     * @return array{0: array<string, array<string, mixed>>, 1: int}  [détail par pilier, score]
     */
    private function scorePillars(GameSave $gameSave, array $targets, int $rank, int $teamId, array $preset): array
    {
        $otherWeight = (100 - $preset['rank_weight']) / 3;

        $values = [
            'rank'     => $rank,
            'strength' => (float) $this->teamStrengths($gameSave)->get($teamId, 0),
            'morale'   => $this->averageMorale($gameSave, $teamId),
            'budget'   => (int) (GameTeam::whereKey($teamId)->value('budget') ?? 0),
        ];

        $pillars = [];
        $score = 0.0;
        foreach (['rank' => 'Classement', 'strength' => 'Effectif', 'morale' => 'Vestiaire', 'budget' => 'Finances'] as $key => $label) {
            $target = $targets[$key]['target'] ?? null;
            if ($target === null) {
                continue;
            }
            $weight = $key === 'rank' ? $preset['rank_weight'] : $otherWeight;
            $met = $key === 'rank' ? $values[$key] <= $target : $values[$key] >= $target;
            $score += $met ? $weight : 0;
            $pillars[$key] = ['label' => $label, 'target' => $target, 'value' => $values[$key], 'met' => $met, 'weight' => round($weight, 1)];
        }

        return [$pillars, (int) round($score)];
    }

    /**
     * Valeurs actuelles de l'équipe contrôlée sur les piliers mesurables à tout moment.
     *
     * @return array{strength: float, morale: int, budget: int}
     */
    private function livePillarValues(GameSave $gameSave): array
    {
        $teamId = (int) ($gameSave->controlled_game_team_id ?? 0);

        return [
            'strength' => (float) $this->teamStrengths($gameSave)->get($teamId, 0),
            'morale'   => $this->averageMorale($gameSave, $teamId),
            'budget'   => (int) (GameTeam::whereKey($teamId)->value('budget') ?? 0),
        ];
    }

    /** Moral moyen arrondi de l'effectif sous contrat de l'équipe (0 si effectif vide). */
    private function averageMorale(GameSave $gameSave, int $teamId): int
    {
        $ids = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $teamId)
            ->activeAt((int) ($gameSave->week ?? 1))
            ->pluck('game_player_id');

        return $ids->isEmpty() ? 0 : (int) round(GamePlayer::whereIn('id', $ids)->avg('morale') ?? 0);
    }

    /**
     * Cibles des 4 piliers du mandat (classement, stats, vestiaire, finances), figées en
     * début de saison avec les valeurs de départ qui servent à mesurer la progression.
     *
     * @param  Collection<int, float>  $strengths
     * @return array<string, array<string, int|float>>
     */
    private function buildPillars(GameSave $gameSave, array $preset, Collection $strengths, int $teamId, int $targetRank, int $expectedRank, int $teamCount): array
    {
        $baseStrength = (float) $strengths->get($teamId);
        $baseBudget   = (int) (GameTeam::whereKey($teamId)->value('budget') ?? 0);
        $income       = WeekClosingService::humanIncomeModel($gameSave)['base'];
        $weeks        = SeasonService::lengthForTeamCount($teamCount);

        return [
            'rank'     => ['target' => $targetRank, 'expected' => $expectedRank],
            'strength' => [
                'baseline' => $baseStrength,
                'target'   => round(min(100, $baseStrength + $preset['strength_gain']), 2),
            ],
            'morale'   => ['target' => min(90, (int) $preset['morale_target'])],
            'budget'   => [
                'baseline' => $baseBudget,
                'target'   => $baseBudget + (int) (round($income * $weeks * $preset['budget_share'] / 50) * 50),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function preset(GameSave $gameSave): array
    {
        $difficulty = $gameSave->getConfig('career_difficulty', 'standard');

        return self::PRESETS[$difficulty] ?? self::PRESETS['standard'];
    }

    /** Borne la jauge de confiance entre 0 et 100. */
    private function clampConfidence(int $value): int
    {
        return max(0, min(self::CONFIDENCE_MAX, $value));
    }

    /** Écrit les données de carrière dans l'état de la partie et sauvegarde la partie (à appeler sous verrou de sauvegarde). */
    private function persist(GameSave $gameSave, array $career): void
    {
        $gameSave->gameState()->putCareer($career);
        $gameSave->save();
    }

    /**
     * Force sportive de chaque équipe (moyenne des stats clés des 11 meilleurs
     * sous contrat), triée par force décroissante. La clé est l'id d'équipe ;
     * la position dans la collection donne le rang « attendu » (0 = plus fort).
     *
     * @return Collection<int, float>
     */
    public function teamStrengths(GameSave $gameSave): Collection
    {
        $currentWeek = (int) ($gameSave->week ?? 1);

        $teams = GameTeam::where('game_save_id', $gameSave->id)->pluck('id');

        // Deux requêtes pour toute la ligue (au lieu de deux par équipe) : les contrats actifs
        // de la semaine regroupés par équipe, puis les joueurs concernés.
        $playerIdsByTeam = GameContract::where('game_save_id', $gameSave->id)
            ->activeAt($currentWeek)
            ->get(['game_team_id', 'game_player_id'])
            ->groupBy('game_team_id')
            ->map(fn ($contracts) => $contracts->pluck('game_player_id')->unique()->values());

        $players = GamePlayer::whereIn('id', $playerIdsByTeam->flatten()->unique()->values())
            ->get()
            ->keyBy('id');

        $strengths = collect();
        foreach ($teams as $teamId) {
            $ratings = ($playerIdsByTeam->get($teamId) ?? collect())
                ->map(fn ($id) => $players->get($id))
                ->filter()
                ->map(fn (GamePlayer $p) => (
                    $p->attack + $p->defense + $p->speed + $p->shot + $p->pass
                    + $p->dribble + $p->block + $p->intercept + $p->tackle + $p->heading
                ) / 10)
                ->sortDesc()
                ->take(11);

            // Équipe sans joueur sous contrat : pas de force calculable, absente du classement.
            if ($ratings->isEmpty()) {
                continue;
            }

            $strengths[$teamId] = round($ratings->avg(), 2);
        }

        return $strengths->sortDesc();
    }
}

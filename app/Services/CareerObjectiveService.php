<?php

namespace App\Services;

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
 * Toute la donnée vit dans GameSave->state['career']. Le mode Coupe du Monde
 * (élimination directe, fin naturelle) n'est pas concerné.
 */
class CareerObjectiveService
{
    /** Réglages par niveau de mandat choisi à la création. */
    public const PRESETS = [
        'survival' => [
            'label'        => 'Survie',
            'confidence'   => 45,
            'titles'       => 1,
            'rank_offset'  => 2,   // objectif plus indulgent (rang plus bas toléré)
            'win_bonus'    => 20,
            'fail_penalty' => 16,
        ],
        'standard' => [
            'label'        => 'Standard',
            'confidence'   => 50,
            'titles'       => 2,
            'rank_offset'  => 0,
            'win_bonus'    => 18,
            'fail_penalty' => 22,
        ],
        'conquest' => [
            'label'        => 'Conquête',
            'confidence'   => 55,
            'titles'       => 3,
            'rank_offset'  => -2,  // objectif exigeant (doit finir plus haut)
            'win_bonus'    => 15,
            'fail_penalty' => 28,
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

    public function isEnabled(GameSave $gameSave): bool
    {
        if ($gameSave->competition_type === 'world_cup') {
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
        $career = $gameSave->state['career'] ?? [];
        $preset = $this->preset($gameSave);

        return array_merge([
            'difficulty'      => $gameSave->getConfig('career_difficulty', 'standard'),
            'confidence'      => $preset['confidence'],
            'status'          => 'active',  // active | fired | won
            'mandate'         => null,
            'titles_required' => $preset['titles'],
            'titles_won'      => 0,
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
            'last_verdict'    => $c['last_verdict'],
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

        $c['mandate'] = [
            'season'        => $season,
            'target_rank'   => $targetRank,
            'expected_rank' => $expectedRank,
            'team_count'    => $teamCount,
            'label'         => $targetRank <= 1
                ? 'Titre de champion'
                : "Finir dans le top {$targetRank}",
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
        $met        = $rank <= $target;
        $isChampion = $rank === 1;

        if ($met) {
            // Dépassement de l'objectif : bonus supplémentaire par rang gagné.
            $delta = $preset['win_bonus'] + max(0, $target - $rank) * 4;
        } else {
            // Échec : pénalité aggravée selon l'ampleur du raté.
            $delta = - (int) round($preset['fail_penalty'] * (1 + 0.25 * ($rank - $target)));
        }

        $c['confidence'] = $this->clampConfidence((int) $c['confidence'] + $delta);

        if ($isChampion) {
            $c['titles_won'] = (int) $c['titles_won'] + 1;
        }

        // Décision : victoire de carrière > licenciement > maintien.
        $outcome = 'retained';
        if ((int) $c['titles_won'] >= (int) $c['titles_required']) {
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
            'champion'    => $isChampion,
            'confidence'  => (int) $c['confidence'],
            'delta'       => $delta,
            'outcome'     => $outcome,
            'titles_won'  => (int) $c['titles_won'],
            'titles_required' => (int) $c['titles_required'],
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

    /** @return array<string, mixed> */
    private function preset(GameSave $gameSave): array
    {
        $difficulty = $gameSave->getConfig('career_difficulty', 'standard');

        return self::PRESETS[$difficulty] ?? self::PRESETS['standard'];
    }

    private function clampConfidence(int $value): int
    {
        return max(0, min(self::CONFIDENCE_MAX, $value));
    }

    private function persist(GameSave $gameSave, array $career): void
    {
        $state = $gameSave->state ?? [];
        $state['career'] = $career;
        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Force sportive de chaque équipe (moyenne des stats clés des 11 meilleurs
     * sous contrat), triée par force décroissante. La clé est l'id d'équipe ;
     * la position dans la collection donne le rang « attendu » (0 = plus fort).
     *
     * @return Collection<int, float>
     */
    private function teamStrengths(GameSave $gameSave): Collection
    {
        $currentWeek = (int) ($gameSave->week ?? 1);

        $teams = GameTeam::where('game_save_id', $gameSave->id)->pluck('id');

        $strengths = collect();
        foreach ($teams as $teamId) {
            $playerIds = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_team_id', $teamId)
                ->activeAt($currentWeek)
                ->pluck('game_player_id');

            if ($playerIds->isEmpty()) {
                continue;
            }

            $ratings = GamePlayer::whereIn('id', $playerIds)
                ->get()
                ->map(fn (GamePlayer $p) => (
                    $p->attack + $p->defense + $p->speed + $p->shot + $p->pass
                    + $p->dribble + $p->block + $p->intercept + $p->tackle + $p->heading
                ) / 10)
                ->sortDesc()
                ->take(11);

            if ($ratings->isEmpty()) {
                continue;
            }

            $strengths[$teamId] = round($ratings->avg(), 2);
        }

        return $strengths->sortDesc();
    }
}

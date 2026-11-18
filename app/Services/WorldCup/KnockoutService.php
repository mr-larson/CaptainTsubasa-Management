<?php

namespace App\Services\WorldCup;

use App\Contracts\Randomizer;
use App\Enums\MatchStatus;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Services\Season\CareerObjectiveService;
use Illuminate\Support\Collection;

/**
 * Phases à élimination directe de la Coupe du Monde : affiches du premier tour, vainqueurs et perdants,
 * tirs au but simulés, tableau (bracket) et équipes éliminées.
 *
 * Domaine : Coupe du Monde. Utilisé par TournamentService (progression des tours) et sa présentation.
 * Tours possibles, dans l'ordre : round16 (8es) → quarter → semi → final + third (match pour la 3e place).
 */
class KnockoutService
{
    /** Tours à élimination directe affichés dans le tableau, dans l'ordre. */
    public const ROUNDS = ['round16', 'quarter', 'semi', 'third', 'final'];

    /** Tour qui suit chacun des tours à élimination directe « en chaîne » (la finale et la 3e place sont gérées à part). */
    public const NEXT_ROUND = ['round16' => 'quarter', 'quarter' => 'semi'];

    public function __construct(private Randomizer $random) {}

    /**
     * Premier tour à élimination directe selon le nombre de groupes, avec ses affiches (domicile, extérieur).
     *   - 1 groupe  : finale directe entre les deux premiers ;
     *   - 2 groupes : demies A1-B2 / B1-A2 ;
     *   - 4 groupes : quarts A1-B2, C1-D2, B1-A2, D1-C2 ;
     *   - 8 groupes : 8es A1-B2, C1-D2, E1-F2, G1-H2, B1-A2, D1-C2, F1-E2, H1-G2 ;
     *   - 6 groupes : 8es avec les 4 meilleurs troisièmes (voir sixGroupPairs).
     * Les affiches sont ordonnées pour que les tours suivants (vainqueurs 1-2, 3-4…) ne recroisent pas
     * deux équipes d'un même groupe avant la finale.
     *
     * @param  array<int, array<int, int>>  $qual  qualifiés par groupe (1ᵉʳ, 2ᵉ), dans l'ordre A, B, C…
     * @param  array<int, int>  $thirds  meilleurs troisièmes (format à 6 groupes), du meilleur au moins bon
     * @param  array<int, int>  $groupOfThird  team_id d'un troisième => index de son groupe (0 = A), pour éviter les revanches de poule
     * @return array{0: string, 1: array<int, array{0:int,1:int}>} [tour, affiches]
     */
    public function firstRound(array $qual, array $thirds = [], array $groupOfThird = []): array
    {
        if (count($qual) < 2) {
            return ['final', isset($qual[0][0], $qual[0][1]) ? [[$qual[0][0], $qual[0][1]]] : []];
        }
        if (count($qual) === 6) {
            return ['round16', $this->sixGroupPairs($qual, $thirds, $groupOfThird)];
        }

        $first = $second = [];
        foreach (array_chunk($qual, 2) as [$a, $b]) {
            if (isset($a[0], $b[1])) {
                $first[] = [$a[0], $b[1]];
            }
            if (isset($b[0], $a[1])) {
                $second[] = [$b[0], $a[1]];
            }
        }

        return [count($qual) >= 8 ? 'round16' : (count($qual) >= 4 ? 'quarter' : 'semi'), array_merge($first, $second)];
    }

    /**
     * 8es de finale du format à 6 groupes : les vainqueurs des groupes A à D rencontrent les 4 meilleurs
     * troisièmes (jamais celui de leur propre groupe, le moins bon troisième affrontant le meilleur
     * vainqueur), E1-F2 et F1-E2 se croisent, et A2-B2 / C2-D2 complètent. L'ordre des 8 affiches garantit
     * que 1ᵉʳ et 2ᵉ d'un même groupe se retrouvent dans des moitiés de tableau opposées.
     *
     * @param  array<int, array<int, int>>  $qual  6 groupes de [1ᵉʳ, 2ᵉ]
     * @param  array<int, int>  $thirds  4 meilleurs troisièmes
     * @param  array<int, int>  $groupOfThird  team_id => index de groupe
     * @return array<int, array{0:int,1:int}>
     */
    private function sixGroupPairs(array $qual, array $thirds, array $groupOfThird): array
    {
        [$a, $b, $c, $d, $e, $f] = $qual;
        $x = $this->assignThirds(array_reverse($thirds), $groupOfThird); // le moins bon troisième d'abord

        return [
            [$a[0], $x[0]], [$c[1], $d[1]],
            [$b[0], $x[1]], [$e[0], $f[1]],
            [$c[0], $x[2]], [$a[1], $b[1]],
            [$d[0], $x[3]], [$f[0], $e[1]],
        ];
    }

    /**
     * Affecte un troisième à chacun des vainqueurs des groupes A à D (positions 0 à 3) sans affronter son
     * propre groupe : première permutation valide dans l'ordre de préférence ; à défaut, l'ordre brut.
     *
     * @param  array<int, int>  $thirds  troisièmes dans l'ordre de préférence
     * @param  array<int, int>  $groupOfThird  team_id => index de groupe
     * @return array<int, int> un troisième par vainqueur (même index)
     */
    private function assignThirds(array $thirds, array $groupOfThird): array
    {
        $try = function (array $remaining, array $assigned) use (&$try, $groupOfThird): ?array {
            $slot = count($assigned);
            if ($slot === 4) {
                return $assigned;
            }
            foreach ($remaining as $i => $team) {
                if (($groupOfThird[$team] ?? -1) === $slot) {
                    continue;
                }
                $left = $remaining;
                unset($left[$i]);
                if ($result = $try($left, [...$assigned, $team])) {
                    return $result;
                }
            }

            return null;
        };

        return $try($thirds, []) ?? array_values($thirds);
    }

    /**
     * Vainqueurs des matchs joués d'un tour à élimination directe (dans l'ordre des matchs).
     *
     * @return array<int, int>
     */
    public function roundWinners(GameSave $gameSave, string $round): array
    {
        $winners = [];
        foreach ($this->playedMatches($gameSave, $round) as $m) {
            $winners[] = $this->matchWinner($m);
        }

        return array_values(array_filter($winners));
    }

    /**
     * Perdants des matchs joués d'un tour à élimination directe (même ordre que roundWinners).
     *
     * @return array<int, int>
     */
    public function roundLosers(GameSave $gameSave, string $round): array
    {
        $losers = [];
        foreach ($this->playedMatches($gameSave, $round) as $m) {
            $losers[] = (int) $this->matchWinner($m) === (int) $m->home_team_id ? (int) $m->away_team_id : (int) $m->home_team_id;
        }

        return $losers;
    }

    /** @return Collection<int, GameMatch> */
    private function playedMatches(GameSave $gameSave, string $round)
    {
        return GameMatch::where('game_save_id', $gameSave->id)->where('round', $round)->where('status', 'played')->orderBy('id')->get();
    }

    /**
     * Probabilité (en %) que l'équipe à domicile gagne une séance de tirs au but simulée.
     * 50 % à force égale, +1 point par point de force d'écart (CareerObjectiveService::teamStrengths),
     * bornée à 35–65 % : la loterie des tirs au but garde le dernier mot.
     */
    public function shootoutHomeChance(GameMatch $m): int
    {
        $save = GameSave::find($m->game_save_id);
        $strengths = $save ? app(CareerObjectiveService::class)->teamStrengths($save) : collect();
        $gap = (float) ($strengths[$m->home_team_id] ?? 0) - (float) ($strengths[$m->away_team_id] ?? 0);

        return (int) max(35, min(65, round(50 + $gap)));
    }

    /**
     * Vainqueur d'un match à élimination directe. Égalité → vainqueur des tirs au but joués dans le moteur
     * (match_stats.shootout) ; pour un match simulé, tirage unique enregistré dans match_stats pour rester
     * stable d'un appel à l'autre.
     */
    public function matchWinner(GameMatch $m): ?int
    {
        $hs = (int) $m->home_score;
        $as = (int) $m->away_score;
        if ($hs > $as) {
            return $m->home_team_id;
        }
        if ($as > $hs) {
            return $m->away_team_id;
        }

        $stats = $m->match_stats ?? [];
        $winner = (int) ($stats['shootout']['winner_team_id'] ?? 0);
        if (in_array($winner, [(int) $m->home_team_id, (int) $m->away_team_id], true)) {
            return $winner;
        }

        $winner = $this->random->int(0, 99) < $this->shootoutHomeChance($m) ? (int) $m->home_team_id : (int) $m->away_team_id;
        $stats['shootout'] = ['winner_team_id' => $winner, 'simulated' => true];
        $m->match_stats = $stats;
        $m->save();

        return $winner;
    }

    /**
     * Tableau à élimination directe prêt pour l'affichage : pour chaque tour présent, les matchs avec
     * équipes, score, vainqueur et tirs au but.
     *
     * @param  array<string, mixed>  $t  state['tournament']
     * @param  callable(?int): ?array  $ref  fabrique la référence d'équipe (nom, drapeau)
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function bracket(GameSave $gameSave, array $t, callable $ref): array
    {
        $bracket = [];
        foreach (self::ROUNDS as $round) {
            $matches = GameMatch::where('game_save_id', $gameSave->id)->where('round', $round)->orderBy('id')->get()->values();
            if ($matches->isEmpty()) {
                continue;
            }

            $stored = $t['winners'][$round] ?? [];
            $bracket[$round] = $matches->map(function ($m, $i) use ($ref, $stored) {
                $played = $m->status === MatchStatus::Played;
                $shootout = $m->match_stats['shootout'] ?? null;
                $winner = null;
                if ($played) {
                    $hs = (int) $m->home_score;
                    $as = (int) $m->away_score;
                    $winner = $hs > $as ? $m->home_team_id : ($as > $hs ? $m->away_team_id : ($shootout['winner_team_id'] ?? $stored[$i] ?? null));
                }
                $soScores = $shootout['scores'] ?? null; // score des tirs au but (séance jouée dans le moteur)

                return [
                    'home' => $ref($m->home_team_id),
                    'away' => $ref($m->away_team_id),
                    'home_score' => $m->home_score,
                    'away_score' => $m->away_score,
                    'played' => $played,
                    'winner_team_id' => $winner,
                    'shootout' => is_array($soScores) ? [
                        'home' => (int) ($soScores[$m->home_team_id] ?? 0),
                        'away' => (int) ($soScores[$m->away_team_id] ?? 0),
                    ] : null,
                ];
            })->all();
        }

        return $bracket;
    }

    /**
     * Sélections déjà sorties du tournoi, avec le tour de leur élimination : 'group' (non qualifiée),
     * 'round16', 'quarter', 'semi' ou 'final' (finaliste battu). Sert à afficher « Éliminé » au manager.
     *
     * @param  array<int, array<string, mixed>>  $groups  poules telles que renvoyées par TournamentService::presentation()
     * @param  array<string, array<int, array<string, mixed>>>  $bracket
     * @return array<int, string> team_id => tour d'élimination
     */
    public function eliminated(string $stage, array $groups, array $bracket): array
    {
        $out = [];
        if ($stage !== 'group') {
            foreach ($groups as $group) {
                foreach ($group['rows'] as $row) {
                    if (! $row['qualified']) {
                        $out[$row['team_id']] = 'group';
                    }
                }
            }
        }

        foreach (['round16', 'quarter', 'semi', 'final'] as $round) {
            foreach ($bracket[$round] ?? [] as $match) {
                if (! $match['played'] || $match['winner_team_id'] === null) {
                    continue;
                }
                $loser = (int) $match['home']['team_id'] === (int) $match['winner_team_id'] ? $match['away']['team_id'] : $match['home']['team_id'];
                $out[$loser] = $round;
            }
        }

        return $out;
    }
}

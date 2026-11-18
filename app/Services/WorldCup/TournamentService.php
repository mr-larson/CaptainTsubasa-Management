<?php

namespace App\Services\WorldCup;

use App\Contracts\Randomizer;
use App\Enums\GamePhase;
use App\Enums\MatchStatus;
use App\Enums\Nationality;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\Nation;
use App\Services\Season\CareerObjectiveService;
use Illuminate\Support\Collection;

/**
 * Moteur de tournoi pour le mode Coupe du Monde (jumeau de SeasonService).
 *
 * Format : phase de poules (round-robin simple par groupe) puis élimination
 * directe, les 2 premiers de chaque groupe se qualifiant.
 *   - 8 sélections  : 2 groupes de 4 → demi-finales (A1-B2, B1-A2) → finale + match pour la 3e place ;
 *   - 16 sélections : 4 groupes de 4 → quarts → demies → finale + match pour la 3e place
 *     (les quarts sont ordonnés pour que deux équipes d'un même groupe ne se recroisent pas en demie).
 *
 * Cycle : generateGroups() à la création, puis advance() après chaque journée
 * (appelé par WeekClosingService) fait progresser group → [quarter] → semi → final → done.
 *
 * Stockage de la structure dans game_saves.state['tournament'] ; chaque match
 * porte son `round` (group_a…group_d / quarter / semi / third / final).
 */
class TournamentService
{
    /** Injecte le générateur aléatoire (tirage des poules). */
    public function __construct(private Randomizer $random) {}

    /** Lettres des groupes possibles (4 groupes à 16 sélections, 2 dès 4, sinon 1). */
    private const GROUP_KEYS = ['A', 'B', 'C', 'D'];

    /**
     * Tire les poules et génère leur calendrier (round-robin simple).
     * Passe la partie en phase 'group'.
     */
    public function generateGroups(GameSave $gameSave): void
    {
        $teamIds = GameTeam::where('game_save_id', $gameSave->id)->pluck('id')->all();
        $teamIds = $this->random->shuffle($teamIds);

        $count = count($teamIds);
        if ($count < 2) {
            return;
        }

        // 4 groupes dès 16 sélections, 2 dès 4 sélections, sinon 1 seul groupe.
        $groupCount = $count >= 16 ? 4 : ($count >= 4 ? 2 : 1);
        $keys = array_slice(self::GROUP_KEYS, 0, $groupCount);

        $groups = array_fill_keys($keys, []);
        foreach ($teamIds as $i => $tid) {
            $groups[$keys[$i % $groupCount]][] = $tid;
        }

        $lastWeek = 1;
        foreach ($groups as $key => $groupTeams) {
            foreach ($this->roundRobin($groupTeams) as $fixture) {
                $week = $fixture['round'] + 1; // journée → semaine
                $this->createMatch($gameSave, $week, $fixture['home'], $fixture['away'], 'group_'.strtolower($key));
                $lastWeek = max($lastWeek, $week);
            }
        }

        $gameSave->gameState()->putTournament([
            'groups' => $groups,
            'stage' => 'group',
            'group_last_week' => $lastWeek,
            'champion_team_id' => null,
        ]);
        $gameSave->phase = GamePhase::Group;
        $gameSave->save();
    }

    /**
     * Fait progresser le tournoi quand le tour courant est terminé :
     * group → (quarts) → demies → finale + 3e place → champion. Sans effet tant que
     * le tour courant n'est pas entièrement joué.
     */
    public function advance(GameSave $gameSave): void
    {
        $t = $gameSave->gameState()->tournament();
        if (! $t) {
            return;
        }

        $stage = $t['stage'] ?? 'group';

        if ($stage === 'group') {
            $groupRounds = array_map(fn ($k) => 'group_'.strtolower($k), array_keys($t['groups']));
            if (! $this->stageComplete($gameSave, $groupRounds)) {
                return;
            }

            // Qualifiés : les 2 premiers de chaque groupe.
            $qual = [];
            foreach ($t['groups'] as $key => $teamIds) {
                $ranked = $this->groupStandings($gameSave, $teamIds, 'group_'.strtolower($key));
                $qual[$key] = array_slice($ranked, 0, 2);
            }

            $week = ($t['group_last_week'] ?? 1) + 1;
            [$round, $pairs] = $this->firstKnockoutPairs(array_values($qual));
            foreach ($pairs as [$home, $away]) {
                $this->createMatch($gameSave, $week, $home, $away, $round);
            }
            $t['stage'] = $round;
            $t['knockout_week'] = $week;
            $gameSave->phase = GamePhase::Knockout;

        } elseif ($stage === 'quarter' || $stage === 'semi') {
            if (! $this->stageComplete($gameSave, [$stage])) {
                return;
            }

            $winners = $this->roundWinners($gameSave, $stage);
            $week = ($t['knockout_week'] ?? ($gameSave->week ?? 1)) + 1;
            $t['winners'][$stage] = $winners; // persiste pour un affichage stable (égalités KO)

            if ($stage === 'quarter') {
                // Demies : vainqueurs des quarts 1-2 et 3-4 (ordre pensé pour éviter les revanches de poule).
                foreach (array_chunk($winners, 2) as $pair) {
                    if (count($pair) === 2) {
                        $this->createMatch($gameSave, $week, $pair[0], $pair[1], 'semi');
                    }
                }
                $t['stage'] = 'semi';
            } else {
                if (count($winners) >= 2) {
                    $this->createMatch($gameSave, $week, $winners[0], $winners[1], 'final');
                }
                // Match pour la 3e place : les deux perdants des demies, joué la même semaine que la finale.
                $losers = $this->roundLosers($gameSave, 'semi');
                if (count($losers) >= 2) {
                    $this->createMatch($gameSave, $week, $losers[0], $losers[1], 'third');
                }
                $t['stage'] = 'final';
                $t['final_week'] = $week;
            }
            $t['knockout_week'] = $week;
            $gameSave->phase = GamePhase::Knockout;

        } elseif ($stage === 'final') {
            if (! $this->stageComplete($gameSave, ['final', 'third'])) {
                return;
            }

            $winners = $this->roundWinners($gameSave, 'final');
            $t['winners']['final'] = $winners;
            $t['winners']['third'] = $this->roundWinners($gameSave, 'third');
            $t['stage'] = 'done';
            $t['champion_team_id'] = $winners[0] ?? null;
            $gameSave->phase = GamePhase::Finished;

        } else {
            return; // 'done'
        }

        $gameSave->gameState()->putTournament($t);
        $gameSave->save();
    }

    /**
     * Premier tour à élimination directe selon le nombre de groupes, avec ses affiches.
     * Un groupe : finale directe. Deux groupes : demies A1-B2 / B1-A2. Quatre groupes :
     * quarts (A1-B2, C1-D2, B1-A2, D1-C2) dont les vainqueurs se croisent 1-2 et 3-4.
     *
     * @param  array<int, array<int, int>>  $qual  qualifiés par groupe, dans l'ordre A, B, C…
     * @return array{0: string, 1: array<int, array{0:int,1:int}>} [tour, affiches (domicile, extérieur)]
     */
    private function firstKnockoutPairs(array $qual): array
    {
        if (count($qual) < 2) {
            return ['final', isset($qual[0][0], $qual[0][1]) ? [[$qual[0][0], $qual[0][1]]] : []];
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

        return [count($qual) >= 4 ? 'quarter' : 'semi', array_merge($first, $second)];
    }

    /** Le tournoi est-il terminé (champion connu) ? */
    public function isTournamentOver(GameSave $gameSave): bool
    {
        return $gameSave->gameState()->tournamentStage() === 'done';
    }

    // ─────────────────────────── Helpers ───────────────────────────

    /**
     * Round-robin simple (un seul match par paire) via la méthode du cercle.
     *
     * @param  array<int, int>  $teamIds
     * @return array<int, array{round:int, home:int, away:int}>
     */
    private function roundRobin(array $teamIds): array
    {
        $ids = array_values($teamIds);
        $n = count($ids);
        if ($n < 2) {
            return [];
        }

        if ($n % 2 === 1) {
            $ids[] = null;
            $n++;
        }

        $rounds = $n - 1;
        $half = intdiv($n, 2);
        $fixtures = [];

        for ($r = 0; $r < $rounds; $r++) {
            for ($i = 0; $i < $half; $i++) {
                $home = $ids[$i];
                $away = $ids[$n - 1 - $i];
                if ($home === null || $away === null) {
                    continue;
                }

                // Alterne le porteur du domicile pour équilibrer.
                $fixtures[] = $r % 2 === 0
                    ? ['round' => $r, 'home' => $home, 'away' => $away]
                    : ['round' => $r, 'home' => $away, 'away' => $home];
            }

            // Rotation : le premier reste fixe, les autres tournent.
            $fixed = array_shift($ids);
            $last = array_pop($ids);
            array_unshift($ids, $fixed);
            array_splice($ids, 1, 0, [$last]);
        }

        return $fixtures;
    }

    /** Crée un match programmé du tournoi pour la semaine et le tour donnés (group_a, group_b, semi, final). */
    private function createMatch(GameSave $gameSave, int $week, int $home, int $away, string $round): void
    {
        GameMatch::create([
            'game_save_id' => $gameSave->id,
            'week' => $week,
            'home_team_id' => $home,
            'away_team_id' => $away,
            'status' => 'scheduled',
            'round' => $round,
        ]);
    }

    /** Tous les matchs de ces tours sont-ils joués (et il en existe au moins un) ? */
    private function stageComplete(GameSave $gameSave, array $rounds): bool
    {
        $base = GameMatch::where('game_save_id', $gameSave->id)->whereIn('round', $rounds);
        $total = (clone $base)->count();
        if ($total === 0) {
            return false;
        }

        return (clone $base)->where('status', 'scheduled')->count() === 0;
    }

    /**
     * Classement complet d'un groupe (J/G/N/P/BP/BC/diff/pts), trié par points, puis
     * confrontations directes entre les ex æquo, puis différence de buts, puis buts pour.
     * Calculé depuis les matchs joués.
     *
     * @param  array<int, int>  $teamIds
     * @return array<int, array<string, mixed>> lignes ordonnées du 1ᵉʳ au dernier
     */
    private function groupTable(GameSave $gameSave, array $teamIds, string $round): array
    {
        $teams = GameTeam::where('game_save_id', $gameSave->id)
            ->whereIn('id', $teamIds)->get()->keyBy('id');

        $rows = [];
        foreach ($teamIds as $tid) {
            $rows[$tid] = [
                'team_id' => $tid,
                'name' => $teams[$tid]->name ?? '?',
                'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'gf' => 0, 'ga' => 0,
            ];
        }

        $matches = GameMatch::where('game_save_id', $gameSave->id)
            ->where('round', $round)
            ->where('status', 'played')
            ->get();

        foreach ($matches as $m) {
            $h = $m->home_team_id;
            $a = $m->away_team_id;
            if (! isset($rows[$h], $rows[$a])) {
                continue;
            }

            $hs = (int) $m->home_score;
            $as = (int) $m->away_score;
            $rows[$h]['played']++;
            $rows[$a]['played']++;
            $rows[$h]['gf'] += $hs;
            $rows[$h]['ga'] += $as;
            $rows[$a]['gf'] += $as;
            $rows[$a]['ga'] += $hs;

            if ($hs > $as) {
                $rows[$h]['wins']++;
                $rows[$a]['losses']++;
            } elseif ($hs < $as) {
                $rows[$a]['wins']++;
                $rows[$h]['losses']++;
            } else {
                $rows[$h]['draws']++;
                $rows[$a]['draws']++;
            }
        }

        $rows = array_values($rows);
        foreach ($rows as &$r) {
            $r['points'] = $r['wins'] * 3 + $r['draws'];
            $r['gd'] = $r['gf'] - $r['ga'];
        }
        unset($r);

        usort($rows, fn ($x, $y) => [$y['points'], $y['gd'], $y['gf'], $x['team_id']] <=> [$x['points'], $x['gd'], $x['gf'], $y['team_id']]
        );

        return $this->applyHeadToHead($rows, $matches);
    }

    /**
     * Départage les équipes à égalité de points par les confrontations directes (points, puis
     * différence de buts, puis buts marqués entre elles), avant la différence de buts et les buts
     * marqués généraux (déjà dans l'ordre d'entrée). Comme au Mondial : l'égalité se règle d'abord
     * entre les équipes concernées, pas sur l'ensemble du groupe.
     *
     * @param  array<int, array<string, mixed>>  $rows  lignes triées points → diff → buts
     * @param  Collection<int, GameMatch>  $matches  matchs joués du groupe
     * @return array<int, array<string, mixed>>
     */
    private function applyHeadToHead(array $rows, $matches): array
    {
        $result = [];
        foreach (collect($rows)->groupBy('points') as $tied) {
            $tied = $tied->values()->all();
            if (count($tied) > 1) {
                $ids = array_column($tied, 'team_id');
                $mini = array_fill_keys($ids, ['pts' => 0, 'gd' => 0, 'gf' => 0]);
                foreach ($matches as $m) {
                    $h = $m->home_team_id;
                    $a = $m->away_team_id;
                    if (! isset($mini[$h], $mini[$a])) {
                        continue;
                    }
                    $hs = (int) $m->home_score;
                    $as = (int) $m->away_score;
                    $mini[$h]['gf'] += $hs;
                    $mini[$h]['gd'] += $hs - $as;
                    $mini[$a]['gf'] += $as;
                    $mini[$a]['gd'] += $as - $hs;
                    if ($hs > $as) {
                        $mini[$h]['pts'] += 3;
                    } elseif ($hs < $as) {
                        $mini[$a]['pts'] += 3;
                    } else {
                        $mini[$h]['pts']++;
                        $mini[$a]['pts']++;
                    }
                }
                // usort est stable (PHP 8) : à confrontations égales, l'ordre général (diff, buts) est conservé.
                usort($tied, fn ($x, $y) => [$mini[$y['team_id']]['pts'], $mini[$y['team_id']]['gd'], $mini[$y['team_id']]['gf']]
                    <=> [$mini[$x['team_id']]['pts'], $mini[$x['team_id']]['gd'], $mini[$x['team_id']]['gf']]);
            }
            array_push($result, ...$tied);
        }

        return $result;
    }

    /**
     * Classement d'un groupe réduit aux ids d'équipes (du 1ᵉʳ au dernier).
     *
     * @param  array<int, int>  $teamIds
     * @return array<int, int>
     */
    private function groupStandings(GameSave $gameSave, array $teamIds, string $round): array
    {
        return array_column($this->groupTable($gameSave, $teamIds, $round), 'team_id');
    }

    /**
     * Structure du tournoi prête pour l'affichage (poules + bracket + champion).
     * Retourne null si le tournoi n'a pas encore été généré.
     *
     * @return array<string, mixed>|null
     */
    public function presentation(GameSave $gameSave): ?array
    {
        $t = $gameSave->gameState()->tournament();
        if (! $t) {
            return null;
        }

        $teams = GameTeam::where('game_save_id', $gameSave->id)->get()->keyBy('id');
        $flagUrls = Nation::flagUrls(); // drapeaux image (l'emoji reste le repli)
        $ref = function (?int $id) use ($teams, $flagUrls): ?array {
            if (! $id || ! isset($teams[$id])) {
                return null;
            }

            return ['team_id' => $id, 'name' => $teams[$id]->name, 'flag' => Nationality::flag($teams[$id]->name), 'flag_url' => $flagUrls[$teams[$id]->name] ?? null];
        };

        // Poules
        $groups = [];
        foreach (($t['groups'] ?? []) as $key => $teamIds) {
            $rows = $this->groupTable($gameSave, $teamIds, 'group_'.strtolower($key));
            foreach ($rows as $i => &$r) {
                $r['flag'] = Nationality::flag($r['name']);
                $r['flag_url'] = $flagUrls[$r['name']] ?? null;
                $r['qualified'] = $i < 2; // les 2 premiers passent
            }
            unset($r);
            $groups[] = ['key' => $key, 'rows' => $rows];
        }

        // Bracket : demies puis finale. Vainqueur depuis le score, ou (égalité)
        // depuis les vainqueurs persistés à l'avancement.
        $bracket = [];
        foreach (['quarter', 'semi', 'third', 'final'] as $round) {
            $matches = GameMatch::where('game_save_id', $gameSave->id)
                ->where('round', $round)->orderBy('id')->get()->values();
            if ($matches->isEmpty()) {
                continue;
            }

            $stored = $t['winners'][$round] ?? [];
            $bracket[$round] = $matches->map(function ($m, $i) use ($ref, $stored) {
                $winner = null;
                $shootout = $m->match_stats['shootout'] ?? null;
                if ($m->status === MatchStatus::Played) {
                    $hs = (int) $m->home_score;
                    $as = (int) $m->away_score;
                    $winner = $hs > $as ? $m->home_team_id
                        : ($as > $hs ? $m->away_team_id : ($shootout['winner_team_id'] ?? $stored[$i] ?? null));
                }
                // Score des tirs au but (séance jouée dans le moteur uniquement).
                $soScores = $shootout['scores'] ?? null;

                return [
                    'home' => $ref($m->home_team_id),
                    'away' => $ref($m->away_team_id),
                    'home_score' => $m->home_score,
                    'away_score' => $m->away_score,
                    'played' => $m->status === MatchStatus::Played,
                    'winner_team_id' => $winner,
                    'shootout' => is_array($soScores) ? [
                        'home' => (int) ($soScores[$m->home_team_id] ?? 0),
                        'away' => (int) ($soScores[$m->away_team_id] ?? 0),
                    ] : null,
                ];
            })->all();
        }

        return [
            'stage' => $t['stage'] ?? 'group',
            'groups' => $groups,
            'bracket' => $bracket,
            'champion' => $ref($t['champion_team_id'] ?? null),
            'eliminated' => $this->eliminatedTeams($t['stage'] ?? 'group', $groups, $bracket),
        ];
    }

    /**
     * Sélections déjà sorties du tournoi, avec le tour de leur élimination :
     * 'group' (non qualifiée), 'quarter', 'semi' (battue en demie) ou 'final' (finaliste battu).
     * Sert à afficher « Éliminé » au manager au lieu de le laisser sans repère.
     *
     * @param  array<int, array<string, mixed>>  $groups  poules telles que renvoyées par presentation()
     * @param  array<string, array<int, array<string, mixed>>>  $bracket
     * @return array<int, string> team_id => tour d'élimination
     */
    private function eliminatedTeams(string $stage, array $groups, array $bracket): array
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

        foreach (['quarter', 'semi', 'final'] as $round) {
            foreach ($bracket[$round] ?? [] as $match) {
                if (! $match['played'] || $match['winner_team_id'] === null) {
                    continue;
                }
                $loser = (int) $match['home']['team_id'] === (int) $match['winner_team_id']
                    ? $match['away']['team_id']
                    : $match['home']['team_id'];
                $out[$loser] = $round;
            }
        }

        return $out;
    }

    /**
     * Vainqueurs des matchs joués d'un tour à élimination directe.
     *
     * @return array<int, int>
     */
    private function roundWinners(GameSave $gameSave, string $round): array
    {
        $matches = GameMatch::where('game_save_id', $gameSave->id)
            ->where('round', $round)
            ->where('status', 'played')
            ->orderBy('id')
            ->get();

        $winners = [];
        foreach ($matches as $m) {
            $winners[] = $this->matchWinner($m);
        }

        return array_values(array_filter($winners));
    }

    /**
     * Perdants des matchs joués d'un tour à élimination directe (même ordre que roundWinners).
     *
     * @return array<int, int>
     */
    private function roundLosers(GameSave $gameSave, string $round): array
    {
        $matches = GameMatch::where('game_save_id', $gameSave->id)
            ->where('round', $round)->where('status', 'played')->orderBy('id')->get();

        $losers = [];
        foreach ($matches as $m) {
            $winner = $this->matchWinner($m);
            $losers[] = (int) $winner === (int) $m->home_team_id ? (int) $m->away_team_id : (int) $m->home_team_id;
        }

        return $losers;
    }

    /**
     * Probabilité (en %) que l'équipe à domicile gagne une séance de tirs au but simulée.
     * 50 % à force égale, +1 point par point de force d'écart (CareerObjectiveService::teamStrengths),
     * bornée à 35–65 % : la loterie des tirs au but garde le dernier mot.
     */
    private function shootoutHomeChance(GameMatch $m): int
    {
        $save = GameSave::find($m->game_save_id);
        $strengths = $save ? app(CareerObjectiveService::class)->teamStrengths($save) : collect();
        $gap = (float) ($strengths[$m->home_team_id] ?? 0) - (float) ($strengths[$m->away_team_id] ?? 0);

        return (int) max(35, min(65, round(50 + $gap)));
    }

    /**
     * Vainqueur d'un match à élimination directe. Égalité → vainqueur des tirs
     * au but joués dans le moteur (match_stats.shootout) ; pour un match simulé,
     * tirage unique enregistré dans match_stats pour rester stable d'un appel à l'autre.
     */
    private function matchWinner(GameMatch $m): ?int
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

        $winner = $this->random->int(0, 99) < $this->shootoutHomeChance($m)
            ? (int) $m->home_team_id
            : (int) $m->away_team_id;
        $stats['shootout'] = ['winner_team_id' => $winner, 'simulated' => true];
        $m->match_stats = $stats;
        $m->save();

        return $winner;
    }
}

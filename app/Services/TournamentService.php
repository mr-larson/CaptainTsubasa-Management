<?php

namespace App\Services;

use App\Enums\Nationality;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;

/**
 * Moteur de tournoi pour le mode Coupe du Monde (jumeau de SeasonService).
 *
 * Format : phase de poules (round-robin simple par groupe) puis élimination
 * directe. Avec 8 sélections : 2 groupes de 4 → les 2 premiers de chaque groupe
 * → demi-finales (A1-B2, B1-A2) → finale.
 *
 * Cycle : generateGroups() à la création, puis advance() après chaque journée
 * (appelé par GameMatchController) fait progresser group → semi → final → done.
 *
 * Stockage de la structure dans game_saves.state['tournament'] ; chaque match
 * porte son `round` (group_a/group_b/semi/final).
 */
class TournamentService
{
    private const GROUP_KEYS = ['A', 'B', 'C', 'D'];

    /**
     * Tire les poules et génère leur calendrier (round-robin simple).
     * Passe la partie en phase 'group'.
     */
    public function generateGroups(GameSave $gameSave): void
    {
        $teamIds = GameTeam::where('game_save_id', $gameSave->id)->pluck('id')->all();
        shuffle($teamIds);

        $count = count($teamIds);
        if ($count < 2) return;

        // 2 groupes dès qu'on a au moins 4 sélections, sinon 1 seul groupe.
        $groupCount = $count >= 4 ? 2 : 1;
        $keys       = array_slice(self::GROUP_KEYS, 0, $groupCount);

        $groups = array_fill_keys($keys, []);
        foreach ($teamIds as $i => $tid) {
            $groups[$keys[$i % $groupCount]][] = $tid;
        }

        $lastWeek = 1;
        foreach ($groups as $key => $groupTeams) {
            foreach ($this->roundRobin($groupTeams) as $fixture) {
                $week = $fixture['round'] + 1; // journée → semaine
                $this->createMatch($gameSave, $week, $fixture['home'], $fixture['away'], 'group_' . strtolower($key));
                $lastWeek = max($lastWeek, $week);
            }
        }

        $state = $gameSave->state ?? [];
        $state['tournament'] = [
            'groups'           => $groups,
            'stage'            => 'group',
            'group_last_week'  => $lastWeek,
            'champion_team_id' => null,
        ];
        $gameSave->state = $state;
        $gameSave->phase = 'group';
        $gameSave->save();
    }

    /**
     * Fait progresser le tournoi quand le tour courant est terminé :
     * group → (demies ou finale) → finale → champion. Sans effet tant que le
     * tour courant n'est pas entièrement joué.
     */
    public function advance(GameSave $gameSave): void
    {
        $state = $gameSave->state ?? [];
        $t     = $state['tournament'] ?? null;
        if (! $t) return;

        $stage = $t['stage'] ?? 'group';

        if ($stage === 'group') {
            $groupRounds = array_map(fn($k) => 'group_' . strtolower($k), array_keys($t['groups']));
            if (! $this->stageComplete($gameSave, $groupRounds)) return;

            // Qualifiés : les 2 premiers de chaque groupe.
            $qual = [];
            foreach ($t['groups'] as $key => $teamIds) {
                $ranked     = $this->groupStandings($gameSave, $teamIds, 'group_' . strtolower($key));
                $qual[$key] = array_slice($ranked, 0, 2);
            }

            $week = ($t['group_last_week'] ?? 1) + 1;
            $keys = array_keys($qual);

            if (count($keys) >= 2) {
                // Demi-finales croisées : A1-B2, B1-A2.
                $a = $qual[$keys[0]];
                $b = $qual[$keys[1]];
                if (isset($a[0], $b[1])) $this->createMatch($gameSave, $week, $a[0], $b[1], 'semi');
                if (isset($b[0], $a[1])) $this->createMatch($gameSave, $week, $b[0], $a[1], 'semi');
                $t['stage'] = 'semi';
            } else {
                // Un seul groupe : finale directe entre les 2 premiers.
                $a = $qual[$keys[0]];
                if (isset($a[0], $a[1])) $this->createMatch($gameSave, $week, $a[0], $a[1], 'final');
                $t['stage'] = 'final';
            }
            $t['knockout_week'] = $week;
            $gameSave->phase    = 'knockout';

        } elseif ($stage === 'semi') {
            if (! $this->stageComplete($gameSave, ['semi'])) return;

            $winners = $this->roundWinners($gameSave, 'semi');
            $week    = ($t['knockout_week'] ?? ($gameSave->week ?? 1)) + 1;
            if (count($winners) >= 2) {
                $this->createMatch($gameSave, $week, $winners[0], $winners[1], 'final');
            }
            $t['winners']['semi'] = $winners; // persiste pour un affichage stable (égalités KO)
            $t['stage']      = 'final';
            $t['final_week'] = $week;
            $gameSave->phase = 'knockout';

        } elseif ($stage === 'final') {
            if (! $this->stageComplete($gameSave, ['final'])) return;

            $winners                = $this->roundWinners($gameSave, 'final');
            $t['winners']['final']  = $winners;
            $t['stage']             = 'done';
            $t['champion_team_id']  = $winners[0] ?? null;
            $gameSave->phase        = 'finished';

        } else {
            return; // 'done'
        }

        $state['tournament'] = $t;
        $gameSave->state     = $state;
        $gameSave->save();
    }

    /** Le tournoi est-il terminé (champion connu) ? */
    public function isTournamentOver(GameSave $gameSave): bool
    {
        return ($gameSave->state['tournament']['stage'] ?? null) === 'done';
    }

    // ─────────────────────────── Helpers ───────────────────────────

    /**
     * Round-robin simple (un seul match par paire) via la méthode du cercle.
     *
     * @param  array<int, int> $teamIds
     * @return array<int, array{round:int, home:int, away:int}>
     */
    private function roundRobin(array $teamIds): array
    {
        $ids = array_values($teamIds);
        $n   = count($ids);
        if ($n < 2) return [];

        if ($n % 2 === 1) { $ids[] = null; $n++; }

        $rounds   = $n - 1;
        $half     = intdiv($n, 2);
        $fixtures = [];

        for ($r = 0; $r < $rounds; $r++) {
            for ($i = 0; $i < $half; $i++) {
                $home = $ids[$i];
                $away = $ids[$n - 1 - $i];
                if ($home === null || $away === null) continue;

                // Alterne le porteur du domicile pour équilibrer.
                $fixtures[] = $r % 2 === 0
                    ? ['round' => $r, 'home' => $home, 'away' => $away]
                    : ['round' => $r, 'home' => $away, 'away' => $home];
            }

            // Rotation : le premier reste fixe, les autres tournent.
            $fixed = array_shift($ids);
            $last  = array_pop($ids);
            array_unshift($ids, $fixed);
            array_splice($ids, 1, 0, [$last]);
        }

        return $fixtures;
    }

    private function createMatch(GameSave $gameSave, int $week, int $home, int $away, string $round): void
    {
        GameMatch::create([
            'game_save_id' => $gameSave->id,
            'week'         => $week,
            'home_team_id' => $home,
            'away_team_id' => $away,
            'status'       => 'scheduled',
            'round'        => $round,
        ]);
    }

    /** Tous les matchs de ces tours sont-ils joués (et il en existe au moins un) ? */
    private function stageComplete(GameSave $gameSave, array $rounds): bool
    {
        $base  = GameMatch::where('game_save_id', $gameSave->id)->whereIn('round', $rounds);
        $total = (clone $base)->count();
        if ($total === 0) return false;

        return (clone $base)->where('status', 'scheduled')->count() === 0;
    }

    /**
     * Classement complet d'un groupe (J/G/N/P/BP/BC/diff/pts), trié par points,
     * puis différence de buts, puis buts pour. Calculé depuis les matchs joués.
     *
     * @param  array<int, int> $teamIds
     * @return array<int, array<string, mixed>>  lignes ordonnées du 1ᵉʳ au dernier
     */
    private function groupTable(GameSave $gameSave, array $teamIds, string $round): array
    {
        $teams = GameTeam::where('game_save_id', $gameSave->id)
            ->whereIn('id', $teamIds)->get()->keyBy('id');

        $rows = [];
        foreach ($teamIds as $tid) {
            $rows[$tid] = [
                'team_id' => $tid,
                'name'    => $teams[$tid]->name ?? '?',
                'played'  => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'gf' => 0, 'ga' => 0,
            ];
        }

        $matches = GameMatch::where('game_save_id', $gameSave->id)
            ->where('round', $round)
            ->where('status', 'played')
            ->get();

        foreach ($matches as $m) {
            $h = $m->home_team_id; $a = $m->away_team_id;
            if (! isset($rows[$h], $rows[$a])) continue;

            $hs = (int) $m->home_score; $as = (int) $m->away_score;
            $rows[$h]['played']++; $rows[$a]['played']++;
            $rows[$h]['gf'] += $hs; $rows[$h]['ga'] += $as;
            $rows[$a]['gf'] += $as; $rows[$a]['ga'] += $hs;

            if     ($hs > $as) { $rows[$h]['wins']++;   $rows[$a]['losses']++; }
            elseif ($hs < $as) { $rows[$a]['wins']++;   $rows[$h]['losses']++; }
            else               { $rows[$h]['draws']++;  $rows[$a]['draws']++;  }
        }

        $rows = array_values($rows);
        foreach ($rows as &$r) {
            $r['points'] = $r['wins'] * 3 + $r['draws'];
            $r['gd']     = $r['gf'] - $r['ga'];
        }
        unset($r);

        usort($rows, fn($x, $y) =>
            [$y['points'], $y['gd'], $y['gf']] <=> [$x['points'], $x['gd'], $x['gf']]
        );

        return $rows;
    }

    /**
     * Classement d'un groupe réduit aux ids d'équipes (du 1ᵉʳ au dernier).
     *
     * @param  array<int, int> $teamIds
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
        $t = $gameSave->state['tournament'] ?? null;
        if (! $t) return null;

        $teams = GameTeam::where('game_save_id', $gameSave->id)->get()->keyBy('id');
        $ref   = function (?int $id) use ($teams): ?array {
            if (! $id || ! isset($teams[$id])) return null;
            return ['team_id' => $id, 'name' => $teams[$id]->name, 'flag' => Nationality::flag($teams[$id]->name)];
        };

        // Poules
        $groups = [];
        foreach (($t['groups'] ?? []) as $key => $teamIds) {
            $rows = $this->groupTable($gameSave, $teamIds, 'group_' . strtolower($key));
            foreach ($rows as $i => &$r) {
                $r['flag']      = Nationality::flag($r['name']);
                $r['qualified'] = $i < 2; // les 2 premiers passent
            }
            unset($r);
            $groups[] = ['key' => $key, 'rows' => $rows];
        }

        // Bracket : demies puis finale. Vainqueur depuis le score, ou (égalité)
        // depuis les vainqueurs persistés à l'avancement.
        $bracket = [];
        foreach (['semi', 'final'] as $round) {
            $matches = GameMatch::where('game_save_id', $gameSave->id)
                ->where('round', $round)->orderBy('id')->get()->values();
            if ($matches->isEmpty()) continue;

            $stored = $t['winners'][$round] ?? [];
            $bracket[$round] = $matches->map(function ($m, $i) use ($ref, $stored) {
                $winner = null;
                if ($m->status === 'played') {
                    $hs = (int) $m->home_score; $as = (int) $m->away_score;
                    $winner = $hs > $as ? $m->home_team_id : ($as > $hs ? $m->away_team_id : ($stored[$i] ?? null));
                }
                return [
                    'home'           => $ref($m->home_team_id),
                    'away'           => $ref($m->away_team_id),
                    'home_score'     => $m->home_score,
                    'away_score'     => $m->away_score,
                    'played'         => $m->status === 'played',
                    'winner_team_id' => $winner,
                ];
            })->all();
        }

        return [
            'stage'    => $t['stage'] ?? 'group',
            'groups'   => $groups,
            'bracket'  => $bracket,
            'champion' => $ref($t['champion_team_id'] ?? null),
        ];
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

    /** Vainqueur d'un match à élimination directe (égalité → tirs au but, aléatoire). */
    private function matchWinner(GameMatch $m): ?int
    {
        $hs = (int) $m->home_score;
        $as = (int) $m->away_score;

        if ($hs > $as) return $m->home_team_id;
        if ($as > $hs) return $m->away_team_id;

        return rand(0, 1) === 1 ? $m->home_team_id : $m->away_team_id;
    }
}

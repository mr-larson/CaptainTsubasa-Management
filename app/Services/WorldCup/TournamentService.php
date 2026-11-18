<?php

namespace App\Services\WorldCup;

use App\Contracts\Randomizer;
use App\Enums\GamePhase;
use App\Enums\Nationality;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\Nation;

/**
 * Moteur de tournoi pour le mode Coupe du Monde (jumeau de SeasonService) : orchestre les phases.
 *
 * Format : phase de poules (round-robin simple par groupe de 4) puis élimination directe, les 2 premiers
 * de chaque groupe se qualifiant.
 *   - 8 sélections  : 2 groupes → demies → finale + match pour la 3e place ;
 *   - 16 sélections : 4 groupes → quarts → demies → finale + 3e place ;
 *   - 24 sélections : 6 groupes → 8es (les 2 premiers + les 4 meilleurs troisièmes) → quarts → demies → finale + 3e place ;
 *   - 32 sélections : 8 groupes → 8es → quarts → demies → finale + 3e place.
 *
 * Le détail est réparti entre trois classes :
 *   - TournamentService (ici)  : tirage des poules, calendrier, progression des tours (advance), présentation ;
 *   - GroupStandingsService    : classement des poules et départage (confrontations directes, fair-play, tirage stable) ;
 *   - KnockoutService          : affiches des tours à élimination directe, vainqueurs, tirs au but, tableau.
 *
 * Cycle : generateGroups() à la création, puis advance() après chaque journée (appelé par WeekClosingService)
 * fait progresser group → [round16] → [quarter] → semi → final → done.
 *
 * Stockage de la structure dans game_saves.state['tournament'] ; chaque match porte son `round`
 * (group_a…group_h / round16 / quarter / semi / third / final).
 */
class TournamentService
{
    /** Lettres des groupes : 1 à 8 groupes selon la taille du tournoi. */
    private const GROUP_KEYS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    /** Troisièmes qualifiés d'office dans le format à 6 groupes (24 sélections). */
    private const BEST_THIRDS = 4;

    public function __construct(
        private Randomizer $random,
        private GroupStandingsService $standings,
        private KnockoutService $knockout,
    ) {}

    /** Nombre de groupes selon le nombre de sélections : 8 dès 32, 6 dès 24, 4 dès 16, 2 dès 4, sinon 1. */
    public static function groupCountFor(int $teams): int
    {
        return match (true) {
            $teams >= 32 => 8,
            $teams >= 24 => 6,
            $teams >= 16 => 4,
            $teams >= 4 => 2,
            default => 1,
        };
    }

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

        $groupCount = self::groupCountFor($count);
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
     * group → (8es) → (quarts) → demies → finale + 3e place → champion. Sans effet tant que
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

            // Qualifiés : les 2 premiers de chaque groupe (+ les meilleurs troisièmes à 6 groupes).
            $qual = [];
            $groupOfThird = [];
            foreach (array_values($t['groups']) as $index => $teamIds) {
                $key = array_keys($t['groups'])[$index];
                $ranked = $this->standings->ranking($gameSave, $teamIds, 'group_'.strtolower($key));
                $qual[] = array_slice($ranked, 0, 2);
                if (isset($ranked[2])) {
                    $groupOfThird[$ranked[2]] = $index;
                }
            }
            $thirds = count($qual) === 6 ? $this->standings->bestThirds($gameSave, $t['groups'], self::BEST_THIRDS) : [];
            $t['best_thirds'] = $thirds;

            $week = ($t['group_last_week'] ?? 1) + 1;
            [$round, $pairs] = $this->knockout->firstRound($qual, $thirds, $groupOfThird);
            foreach ($pairs as [$home, $away]) {
                $this->createMatch($gameSave, $week, $home, $away, $round);
            }
            $t['stage'] = $round;
            $t['knockout_week'] = $week;
            $gameSave->phase = GamePhase::Knockout;

        } elseif (isset(KnockoutService::NEXT_ROUND[$stage]) || $stage === 'semi') {
            if (! $this->stageComplete($gameSave, [$stage])) {
                return;
            }

            $winners = $this->knockout->roundWinners($gameSave, $stage);
            $week = ($t['knockout_week'] ?? ($gameSave->week ?? 1)) + 1;
            $t['winners'][$stage] = $winners; // persiste pour un affichage stable (égalités KO)

            if ($next = KnockoutService::NEXT_ROUND[$stage] ?? null) {
                // Tour suivant : vainqueurs 1-2, 3-4… (ordre pensé pour éviter les revanches de poule).
                foreach (array_chunk($winners, 2) as $pair) {
                    if (count($pair) === 2) {
                        $this->createMatch($gameSave, $week, $pair[0], $pair[1], $next);
                    }
                }
                $t['stage'] = $next;
            } else {
                if (count($winners) >= 2) {
                    $this->createMatch($gameSave, $week, $winners[0], $winners[1], 'final');
                }
                // Match pour la 3e place : les deux perdants des demies, joué la même semaine que la finale.
                $losers = $this->knockout->roundLosers($gameSave, 'semi');
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

            $winners = $this->knockout->roundWinners($gameSave, 'final');
            $t['winners']['final'] = $winners;
            $t['winners']['third'] = $this->knockout->roundWinners($gameSave, 'third');
            $t['stage'] = 'done';
            $t['champion_team_id'] = $winners[0] ?? null;
            $gameSave->phase = GamePhase::Finished;

        } else {
            return; // 'done'
        }

        $gameSave->gameState()->putTournament($t);
        $gameSave->save();
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

        // Poules : les 2 premiers passent, plus les meilleurs troisièmes une fois la phase de poules jouée.
        $bestThirds = $t['best_thirds'] ?? [];
        $groups = [];
        foreach (($t['groups'] ?? []) as $key => $teamIds) {
            $rows = $this->standings->table($gameSave, $teamIds, 'group_'.strtolower($key));
            foreach ($rows as $i => &$r) {
                $r['flag'] = Nationality::flag($r['name']);
                $r['flag_url'] = $flagUrls[$r['name']] ?? null;
                $r['qualified'] = $i < 2 || in_array($r['team_id'], $bestThirds, true);
            }
            unset($r);
            $groups[] = ['key' => $key, 'rows' => $rows];
        }

        $bracket = $this->knockout->bracket($gameSave, $t, $ref);

        return [
            'stage' => $t['stage'] ?? 'group',
            'groups' => $groups,
            'third_slots' => count($t['groups'] ?? []) === 6 ? self::BEST_THIRDS : 0,
            'bracket' => $bracket,
            'champion' => $ref($t['champion_team_id'] ?? null),
            'eliminated' => $this->knockout->eliminated($t['stage'] ?? 'group', $groups, $bracket),
        ];
    }
}

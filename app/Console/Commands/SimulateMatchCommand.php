<?php

namespace App\Console\Commands;

use App\Enums\TeamStyle;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\User;
use App\Services\MatchSimulator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Smoke test du moteur de match.
 *
 * Crée un scénario jetable (1 save, 2 game_teams de 11 joueurs chacune, leurs
 * game_contracts titulaires et un game_match programmé), lance le vrai
 * MatchSimulator puis affiche le score, les stats d'équipe et le déroulé.
 *
 * Tout se déroule dans une transaction annulée à la fin : aucune donnée n'est
 * persistée. Utile pour vérifier le moteur à la main et comme base pour de
 * futurs tests d'engine.
 */
class SimulateMatchCommand extends Command
{
    protected $signature = 'game:simulate
        {--home-style=balanced : Style tactique domicile (offensive|defensive|possession|counter|balanced)}
        {--away-style=counter : Style tactique extérieur (offensive|defensive|possession|counter|balanced)}
        {--events=14 : Nombre d\'actions du déroulé à afficher}';

    protected $description = "Lance le moteur de match (MatchSimulator) sur un scénario jetable et affiche le résultat. Rien n'est persisté.";

    public function handle(MatchSimulator $simulator): int
    {
        $homeStyle = (string) $this->option('home-style');
        $awayStyle = (string) $this->option('away-style');

        foreach ([$homeStyle, $awayStyle] as $style) {
            if (! in_array($style, TeamStyle::TACTICAL_STYLES, true)) {
                $this->error("Style tactique invalide : « {$style} ». Valeurs possibles : " . implode(', ', TeamStyle::TACTICAL_STYLES));
                return self::FAILURE;
            }
        }

        $this->warn("⚠️  Scénario jetable : tout est créé dans une transaction puis annulé (rollback). Rien n'est persisté en base.");

        DB::beginTransaction();

        try {
            $scenario = $this->buildScenario($homeStyle, $awayStyle);
            $simulator->simulateMatchesCollection(collect([$scenario['match']]));
            $scenario['match']->refresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Échec de la simulation : ' . $e->getMessage());
            $this->line($e->getFile() . ':' . $e->getLine());
            return self::FAILURE;
        }

        $this->renderResult($scenario, (int) $this->option('events'));

        DB::rollBack();

        return self::SUCCESS;
    }

    /**
     * Crée save + 2 équipes complètes + match programmé.
     *
     * @return array{save: GameSave, home: GameTeam, away: GameTeam, match: GameMatch}
     */
    private function buildScenario(string $homeStyle, string $awayStyle): array
    {
        $user = User::factory()->create();

        $save = GameSave::create([
            'user_id' => $user->id,
            'week'    => 1,
            'season'  => 1,
            'label'   => 'game:simulate (smoke)',
            'state'   => [],
        ]);

        $home = $this->buildTeam($save, 'Nankatsu SC', $homeStyle);
        $away = $this->buildTeam($save, 'Toho Academy', $awayStyle);

        $match = GameMatch::create([
            'game_save_id' => $save->id,
            'week'         => 1,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'status'       => 'scheduled',
        ]);

        return ['save' => $save, 'home' => $home, 'away' => $away, 'match' => $match];
    }

    /**
     * Une équipe de 11 titulaires (1 GK, 4 DF, 3 MF, 3 FW), avec contrats actifs.
     */
    private function buildTeam(GameSave $save, string $name, string $style): GameTeam
    {
        $team = GameTeam::create([
            'game_save_id'   => $save->id,
            'name'           => $name,
            'tactical_style' => $style,
            'formation'      => '3-2-3-2',
        ]);

        $blueprint = ['GK' => 1, 'DF' => 4, 'MF' => 3, 'FW' => 3];

        foreach ($blueprint as $position => $count) {
            for ($i = 0; $i < $count; $i++) {
                $player = GamePlayer::create(array_merge(
                    [
                        'game_save_id' => $save->id,
                        'firstname'    => fake()->firstName(),
                        'lastname'     => fake()->lastName(),
                        'position'     => $position,
                    ],
                    $this->statsForPosition($position),
                ));

                GameContract::create([
                    'game_save_id'   => $save->id,
                    'game_team_id'   => $team->id,
                    'game_player_id' => $player->id,
                    'salary'         => 100,
                    'start_week'     => 1,
                    'end_week'       => null,
                    'is_starter'     => true,
                ]);
            }
        }

        return $team;
    }

    /**
     * Stats cohérentes avec le poste (le gardien concentre hand_save/punch_save).
     *
     * @return array<string, int>
     */
    private function statsForPosition(string $position): array
    {
        $isGk = $position === 'GK';

        return [
            'speed'      => random_int(50, 85),
            'stamina'    => random_int(60, 95),
            'attack'     => $position === 'FW' ? random_int(70, 92) : random_int(40, 70),
            'defense'    => $position === 'DF' ? random_int(70, 92) : random_int(40, 70),
            'shot'       => $position === 'FW' ? random_int(70, 92) : random_int(40, 70),
            'pass'       => $position === 'MF' ? random_int(70, 92) : random_int(45, 78),
            'dribble'    => random_int(50, 85),
            'block'      => $position === 'DF' ? random_int(65, 88) : random_int(40, 65),
            'intercept'  => $position === 'DF' ? random_int(65, 88) : random_int(40, 65),
            'tackle'     => $position === 'DF' ? random_int(65, 88) : random_int(40, 65),
            'heading'    => $isGk ? random_int(30, 55) : random_int(45, 82),
            'hand_save'  => $isGk ? random_int(72, 92) : 0,
            'punch_save' => $isGk ? random_int(72, 92) : 0,
        ];
    }

    /**
     * @param array{save: GameSave, home: GameTeam, away: GameTeam, match: GameMatch} $scenario
     */
    private function renderResult(array $scenario, int $eventLimit): void
    {
        /** @var GameMatch $match */
        $match = $scenario['match'];
        $home  = $scenario['home'];
        $away  = $scenario['away'];

        $stats  = $match->match_stats ?? [];
        $events = $stats['events'] ?? [];
        $teams  = $stats['teams'] ?? [];

        $this->newLine();
        $this->line('  <fg=cyan>════════════════ RÉSULTAT ════════════════</>');
        $this->line(sprintf(
            '   <options=bold>%s</>   <fg=yellow;options=bold>%d - %d</>   <options=bold>%s</>',
            $home->name,
            (int) $match->home_score,
            (int) $match->away_score,
            $away->name,
        ));
        $this->line(sprintf(
            '   <fg=gray>%s %s   vs   %s %s</>',
            TeamStyle::TACTICAL_ICONS[$home->tactical_style] ?? '',
            TeamStyle::TACTICAL_LABELS[$home->tactical_style] ?? $home->tactical_style,
            TeamStyle::TACTICAL_ICONS[$away->tactical_style] ?? '',
            TeamStyle::TACTICAL_LABELS[$away->tactical_style] ?? $away->tactical_style,
        ));
        $this->line('  <fg=cyan>══════════════════════════════════════════</>');

        if ($teams) {
            $this->newLine();
            $this->table(
                ['Équipe', 'Buts', 'Tirs', 'Passes', 'Dribbles', 'Duels +', 'Duels -'],
                [
                    $this->teamStatRow($home->name, $teams['home'] ?? []),
                    $this->teamStatRow($away->name, $teams['away'] ?? []),
                ],
            );
        }

        $goals = array_values(array_filter(
            $events,
            fn ($e) => ($e['actionType'] ?? null) === 'goal',
        ));

        $this->newLine();
        $this->line('  <options=bold>⚽ Buts (' . count($goals) . ')</>');
        if ($goals === []) {
            $this->line('   <fg=gray>Aucun but.</>');
        } else {
            foreach ($goals as $g) {
                $this->line(sprintf("   <fg=yellow>%2d'</> %s — %s", $g['turn'] ?? 0, $this->sideName($g, $home, $away), $g['text'] ?? ''));
            }
        }

        if ($eventLimit > 0 && $events !== []) {
            $this->newLine();
            $this->line('  <options=bold>📋 Déroulé (' . min($eventLimit, count($events)) . '/' . count($events) . ' actions)</>');
            foreach (array_slice($events, 0, $eventLimit) as $e) {
                $icon = ($e['result'] ?? null) === 'attack' ? '<fg=green>▶</>' : '<fg=red>◀</>';
                $this->line(sprintf("   %s <fg=gray>%2d'</> [%s] %s", $icon, $e['turn'] ?? 0, $this->sideName($e, $home, $away), $e['text'] ?? ''));
            }
        }

        $this->newLine();
        $this->info('✔ Simulation terminée — transaction annulée, base inchangée.');
    }

    /**
     * @param array<string, mixed> $stats
     * @return array<int, string|int>
     */
    private function teamStatRow(string $name, array $stats): array
    {
        return [
            $name,
            $stats['goals'] ?? 0,
            $stats['shots'] ?? 0,
            $stats['passes'] ?? 0,
            $stats['dribbles'] ?? 0,
            $stats['duelsWon'] ?? 0,
            $stats['duelsLost'] ?? 0,
        ];
    }

    /**
     * @param array<string, mixed> $event
     */
    private function sideName(array $event, GameTeam $home, GameTeam $away): string
    {
        return ($event['team'] ?? null) === 'internal' ? $home->name : $away->name;
    }
}

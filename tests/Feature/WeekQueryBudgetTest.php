<?php

namespace Tests\Feature;

use App\Services\Season\CareerObjectiveService;
use App\Services\Players\PlayerChemistryService;
use App\Services\Season\SeasonChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Budget de requêtes de la clôture de semaine : certaines lectures ne doivent PAS
 * se multiplier par le nombre d'équipes (c'était le cas avant leur regroupement).
 * Ces tests échouent si quelqu'un réintroduit une requête par équipe.
 */
class WeekQueryBudgetTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    /** Nombre de SELECT émis pendant $action. */
    private function selects(callable $action): int
    {
        $count = 0;
        DB::listen(function ($query) use (&$count) {
            if (preg_match('/^\s*select\b/i', $query->sql)) {
                $count++;
            }
        });
        $action();

        return $count;
    }

    /** Une partie avec $teams équipes (une humaine, les autres IA), chacune avec son effectif. */
    private function league(int $teams): array
    {
        $save = $this->makeSave();
        $all  = [];
        foreach (range(1, $teams) as $i) {
            [$team] = $this->makeTeamWithSquad($save, $i === 1 ? ['is_controlled' => true, 'human_seat' => 1] : []);
            $all[] = $team;
        }
        $save->update(['controlled_game_team_id' => $all[0]->id]);

        return [$save->fresh(), $all];
    }

    public function test_team_strengths_use_a_constant_number_of_queries(): void
    {
        [$small] = $this->league(3);
        [$large] = $this->league(10);
        $career = app(CareerObjectiveService::class);

        $few  = $this->selects(fn () => $career->teamStrengths($small));
        $many = $this->selects(fn () => $career->teamStrengths($large));

        $this->assertSame($few, $many, 'Le nombre de requêtes ne doit pas dépendre du nombre d\'équipes.');
        $this->assertLessThanOrEqual(3, $many);
    }

    public function test_team_strengths_rank_the_strongest_team_first_and_skip_empty_teams(): void
    {
        [$save, $teams] = $this->league(3);
        // Équipe 2 : joueurs très forts. Équipe 3 : sans aucun contrat.
        \App\Models\GameSaves\GamePlayer::whereIn('id', \App\Models\GameSaves\GameContract::where('game_team_id', $teams[1]->id)->pluck('game_player_id'))
            ->update(['attack' => 90, 'defense' => 90, 'shot' => 90]);
        \App\Models\GameSaves\GameContract::where('game_team_id', $teams[2]->id)->delete();

        $strengths = app(CareerObjectiveService::class)->teamStrengths($save);

        $this->assertSame([$teams[1]->id, $teams[0]->id], $strengths->keys()->all());
        $this->assertFalse($strengths->has($teams[2]->id));
    }

    public function test_ai_chemistry_attempts_do_not_read_per_team(): void
    {
        [$small] = $this->league(3);
        [$large] = $this->league(10);
        $chemistry = app(PlayerChemistryService::class);

        $few  = $this->selects(fn () => $chemistry->attemptForAiTeams($small));
        $many = $this->selects(fn () => $chemistry->attemptForAiTeams($large));

        $this->assertSame($few, $many, 'Les lectures d\'alchimie ne doivent pas être refaites pour chaque équipe.');
        $this->assertLessThanOrEqual(8, $many);
    }

    public function test_challenge_payout_reads_played_matches_once_for_all_teams(): void
    {
        [$save, $teams] = $this->league(6);
        $service = app(SeasonChallengeService::class);
        $service->ensureSeasonChallenges($save);
        // Chaque équipe reçoit un objectif qui exige l'historique des matchs.
        $state = $save->fresh()->state;
        foreach ($teams as $team) {
            $state['challenges']['teams'][$team->id]['objectives'] = [[
                'key' => 'wins_min', 'tier' => 'easy', 'family' => 'results', 'label' => 'x', 'target' => 99, 'points' => 1, 'reward' => 1,
            ]];
        }
        $save->state = $state;
        $save->save();

        $matchReads = 0;
        DB::listen(function ($query) use (&$matchReads) {
            if (preg_match('/^\s*select\b.*from `game_matches`.*`status` = \?/is', $query->sql)) {
                $matchReads++;
            }
        });
        $service->payCompleted($save->fresh());

        $this->assertLessThanOrEqual(1, $matchReads, 'Les matchs joués doivent être lus une seule fois, pas une fois par équipe.');
    }

    public function test_lineup_snapshot_reuses_preloaded_contracts_and_gives_the_same_result(): void
    {
        [$save, $teams] = $this->league(2);
        $match = $this->makeMatch($save, $teams[0], $teams[1]);
        $snapshots = app(\App\Services\Matches\MatchLineupSnapshot::class);

        // Référence : lecture en base (comportement d'origine).
        $fromDatabase = $snapshots->build($match->fresh());

        // Même match, équipes avec contrats actifs et joueurs déjà chargés (comme MatchSimulator).
        $loaded = $match->fresh();
        $week   = (int) $loaded->week;
        $loaded->load([
            'homeTeam.contracts' => fn ($q) => $q->activeAt($week)->with('gamePlayer'),
            'awayTeam.contracts' => fn ($q) => $q->activeAt($week)->with('gamePlayer'),
            'gameSave',
        ]);

        $fromMemory = null;
        $selects = $this->selects(function () use ($snapshots, $loaded, &$fromMemory) {
            $fromMemory = $snapshots->build($loaded, contractsPreloaded: true);
        });

        $this->assertSame(0, $selects, 'Les contrats déjà chargés doivent être réutilisés.');
        $this->assertEquals($fromDatabase, $fromMemory);
        $this->assertCount(11, $fromMemory['home']['players']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Gardes des actions de « tour » exécutées sous verrou de save
 * (LocksGameSave) : un rejeu (double clic, retry réseau) ne doit jamais
 * appliquer deux fois un match, une semaine ou un changement de saison.
 */
class SaveLockGuardsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_replayed_finish_match_is_counted_once(): void
    {
        $save  = $this->makeSave();
        $human = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $ai    = $this->makeTeam($save);
        $this->makeTeam($save);
        $this->makeTeam($save);
        $save->update(['controlled_game_team_id' => $human->id]);

        $match   = $this->makeMatch($save, $human, $ai);
        $payload = [
            'scoresByTeamId' => [$human->id => 2, $ai->id => 1],
            'playerActions'  => [],
            'foulEvents'     => [],
        ];

        $user = User::find($save->user_id);
        $this->actingAs($user)->post(route('game-saves.matches.finish', [$save, $match]), $payload)->assertRedirect();
        $budgetAfterFirst = (int) $human->fresh()->budget;

        $this->actingAs($user)->post(route('game-saves.matches.finish', [$save, $match]), $payload)->assertRedirect();

        $this->assertSame(1, (int) $human->fresh()->wins);
        $this->assertSame(2, (int) $human->fresh()->goals_for);
        $this->assertSame($budgetAfterFirst, (int) $human->fresh()->budget, 'Les revenus ne doivent pas être versés deux fois.');
        $this->assertSame(2, (int) $save->fresh()->week, 'La semaine ne doit avancer qu\'une fois.');
    }

    public function test_finishing_a_future_week_match_is_rejected(): void
    {
        $save  = $this->makeSave();
        $human = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $ai    = $this->makeTeam($save);
        $save->update(['controlled_game_team_id' => $human->id]);

        $future = $this->makeMatch($save, $human, $ai, ['week' => 5]);

        $this->actingAs(User::find($save->user_id))
            ->post(route('game-saves.matches.finish', [$save, $future]), [
                'scoresByTeamId' => [$human->id => 3, $ai->id => 0],
            ])
            ->assertStatus(422);

        $this->assertSame('scheduled', $future->fresh()->status);
        $this->assertSame(1, (int) $save->fresh()->week);
        $this->assertSame(0, (int) $human->fresh()->wins);
    }

    public function test_simulate_week_is_refused_outside_match_phases(): void
    {
        foreach (['season_end', 'intersaison_draft', 'draft', 'finished'] as $phase) {
            $save = $this->makeSave(['phase' => $phase, 'week' => 7]);
            $team = $this->makeTeam($save, ['budget' => 1000]);

            $this->actingAs(User::find($save->user_id))
                ->post(route('game-saves.simulate-week', $save))
                ->assertRedirect(route('game-saves.Play', $save));

            $this->assertSame(7, (int) $save->fresh()->week, "Phase {$phase} : la semaine ne doit pas avancer.");
            $this->assertSame($phase, $save->fresh()->phase);
            $this->assertSame(1000, (int) $team->fresh()->budget, "Phase {$phase} : aucun revenu ne doit être versé.");
        }
    }

    public function test_simulate_week_with_stale_week_is_ignored(): void
    {
        $save = $this->makeSave(['week' => 3]);
        $team = $this->makeTeam($save, ['budget' => 1000]);

        $this->actingAs(User::find($save->user_id))
            ->post(route('game-saves.simulate-week', $save), ['week' => 2])
            ->assertRedirect(route('game-saves.Play', $save));

        $this->assertSame(3, (int) $save->fresh()->week, 'Un double clic ne doit pas simuler la semaine suivante.');
        $this->assertSame(1000, (int) $team->fresh()->budget);
    }

    public function test_simulate_week_hands_over_to_human_with_pending_match(): void
    {
        $save   = $this->makeSave();
        $seat1  = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $seat2  = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 2]);
        $ai     = $this->makeTeam($save);
        $save->update(['controlled_game_team_id' => $seat1->id]);

        // Le siège 1 est exempt, le siège 2 doit encore jouer.
        $match = $this->makeMatch($save, $seat2, $ai);

        $this->actingAs(User::find($save->user_id))
            ->post(route('game-saves.simulate-week', $save), ['week' => 1])
            ->assertRedirect(route('game-saves.Play', $save));

        $this->assertSame('scheduled', $match->fresh()->status, 'Le match d\'un humain ne doit pas être simulé.');
        $this->assertSame(1, (int) $save->fresh()->week);
        $this->assertSame($seat2->id, (int) $save->fresh()->controlled_game_team_id);
    }

    public function test_replayed_season_continue_starts_only_one_new_season(): void
    {
        $save = $this->makeSave(['phase' => 'season_end', 'week' => 7, 'season' => 1]);
        $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $this->makeTeam($save);

        $user = User::find($save->user_id);
        $this->actingAs($user)->post(route('game-saves.season-end.continue', $save))->assertRedirect();
        $this->actingAs($user)->post(route('game-saves.season-end.continue', $save))->assertRedirect();

        $this->assertSame(2, (int) $save->fresh()->season);
        $this->assertSame('intersaison_draft', $save->fresh()->phase);
    }

    public function test_is_in_match_phase(): void
    {
        foreach (GameSave::MATCH_PHASES as $phase) {
            $this->assertTrue((new GameSave(['phase' => $phase]))->isInMatchPhase(), $phase);
        }
        foreach (['draft', 'intersaison_draft', 'season_end', 'finished'] as $phase) {
            $this->assertFalse((new GameSave(['phase' => $phase]))->isInMatchPhase(), $phase);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class GameRequestValidationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_config_update_rejects_a_min_above_the_max_already_in_force(): void
    {
        $save = $this->makeSave();
        $save->setConfig(['training_gain_max' => 3]);

        $this->actingAs(User::find($save->user_id))
            ->put(route('game-saves.config.update', $save), ['training_gain_min' => 7])
            ->assertSessionHasErrors('training_gain_max');

        $this->assertNotSame(7, (int) $save->fresh()->getConfig('training_gain_min'));
    }

    public function test_config_update_accepts_a_consistent_range(): void
    {
        $save = $this->makeSave();

        $this->actingAs(User::find($save->user_id))
            ->put(route('game-saves.config.update', $save), ['training_gain_min' => 2, 'training_gain_max' => 6])
            ->assertSessionHasNoErrors();

        $this->assertSame(6, (int) $save->fresh()->getConfig('training_gain_max'));
    }

    public function test_formation_rules_share_one_source_of_truth(): void
    {
        $save = $this->makeSave();
        [$team] = $this->makeTeamWithSquad($save, ['is_controlled' => true]);
        $save->update(['controlled_game_team_id' => $team->id]);
        $user = User::find($save->user_id);

        $this->actingAs($user)
            ->post(route('game-saves.lineup.formation', $save), ['team_id' => $team->id, 'formation' => '5-2-3-0'])
            ->assertSessionHasErrors('formation');

        $this->actingAs($user)
            ->post(route('game-saves.lineup.formation', $save), ['team_id' => $team->id, 'formation' => '4-3-2-1'])
            ->assertSessionHasNoErrors();
    }

    public function test_a_stranger_gets_403_before_any_validation_runs(): void
    {
        $save     = $this->makeSave();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->post(route('game-saves.lineup.formation', $save), ['formation' => 'not-a-formation'])
            ->assertForbidden();
    }

    /** Équipe contrôlée par le propriétaire de la partie, avec son effectif de onze. */
    private function controlledTeam($save): array
    {
        [$team, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        $save->update(['controlled_game_team_id' => $team->id]);

        return [$team, $players];
    }

    /** Onze slots construits depuis une liste d'ids de joueurs. */
    private function slots(array $playerIds): array
    {
        return array_map(
            fn ($slot) => ['slot' => $slot, 'player_id' => $playerIds[$slot - 1] ?? null],
            range(1, 11),
        );
    }

    public function test_lineup_accepts_the_team_squad_and_rejects_outsiders_and_duplicates(): void
    {
        $save = $this->makeSave();
        [$team, $players] = $this->controlledTeam($save);
        [, $rivals] = $this->makeTeamWithSquad($save);
        $user = User::find($save->user_id);
        $ids  = $players->pluck('id')->all();
        $url  = route('game-saves.lineup.update', $save);

        $this->actingAs($user)
            ->post($url, ['team_id' => $team->id, 'slots' => $this->slots($ids)])
            ->assertSessionHasNoErrors();

        // Joueur d'un autre club de la même partie.
        $withRival = $ids;
        $withRival[10] = $rivals->first()->id;
        $this->actingAs($user)
            ->post($url, ['team_id' => $team->id, 'slots' => $this->slots($withRival)])
            ->assertSessionHasErrors('slots');

        // Même joueur dans deux slots.
        $withDuplicate = $ids;
        $withDuplicate[10] = $ids[0];
        $this->actingAs($user)
            ->post($url, ['team_id' => $team->id, 'slots' => $this->slots($withDuplicate)])
            ->assertSessionHasErrors();

        $this->assertSame($ids, array_values($save->fresh()->gameState()->lineupSlots($team->id)));
    }

    public function test_ids_from_another_save_are_rejected_by_validation(): void
    {
        $save = $this->makeSave();
        [$team] = $this->controlledTeam($save);
        $user = User::find($save->user_id);

        $other = $this->makeSave(['user_id' => $user->id]);
        [$foreignTeam, $foreignPlayers] = $this->makeTeamWithSquad($other);

        $this->actingAs($user)
            ->post(route('game-saves.lineup.formation', $save), ['team_id' => $foreignTeam->id, 'formation' => '4-3-2-1'])
            ->assertSessionHasErrors('team_id');

        $this->actingAs($user)
            ->post(route('game-saves.position-training.store', $save), ['player_id' => $foreignPlayers->first()->id, 'position' => 'GK'])
            ->assertSessionHasErrors('player_id');
    }

    public function test_finish_match_is_refused_for_a_match_the_active_team_does_not_play(): void
    {
        $save = $this->makeSave();
        [$team] = $this->controlledTeam($save);
        $aiHome = $this->makeTeam($save);
        $aiAway = $this->makeTeam($save);
        $match  = $this->makeMatch($save, $aiHome, $aiAway);
        $user   = User::find($save->user_id);

        $this->actingAs($user)
            ->post(route('game-saves.matches.finish', [$save, $match]), [
                'scoresByTeamId' => [$aiHome->id => 9, $aiAway->id => 0],
            ])
            ->assertForbidden();

        $this->assertNull($match->fresh()->home_score);
    }

    public function test_finish_match_bounds_scores_and_fouls(): void
    {
        $save = $this->makeSave();
        [$team] = $this->controlledTeam($save);
        $ai    = $this->makeTeam($save);
        $match = $this->makeMatch($save, $team, $ai);
        $user  = User::find($save->user_id);
        $url   = route('game-saves.matches.finish', [$save, $match]);

        $this->actingAs($user)
            ->post($url, ['scoresByTeamId' => [$team->id => 1000, $ai->id => 0]])
            ->assertSessionHasErrors();

        $this->actingAs($user)
            ->post($url, [
                'scoresByTeamId' => [$team->id => 1, $ai->id => 0],
                'foulEvents'     => array_fill(0, 301, ['type' => 'card']),
            ])
            ->assertSessionHasErrors('foulEvents');
    }
}

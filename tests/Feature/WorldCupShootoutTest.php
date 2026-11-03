<?php

namespace Tests\Feature;

use App\Models\GameSaves\GamePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Coupe du Monde : une égalité en phase finale se départage aux tirs au but
 * joués dans le moteur (match_stats.shootout), et le marché est fermé.
 */
class WorldCupShootoutTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function knockoutSetup(): array
    {
        $save  = $this->makeSave(['phase' => 'knockout', 'competition_type' => 'world_cup']);
        $human = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $ai    = $this->makeTeam($save);
        $save->update(['controlled_game_team_id' => $human->id]);
        $match = $this->makeMatch($save, $human, $ai, ['round' => 'semi']);

        return [$save, $human, $ai, $match];
    }

    public function test_engine_shootout_winner_is_stored_for_a_knockout_draw(): void
    {
        [$save, $human, $ai, $match] = $this->knockoutSetup();

        $this->actingAs(User::find($save->user_id))
            ->post(route('game-saves.matches.finish', [$save, $match]), [
                'scoresByTeamId' => [$human->id => 1, $ai->id => 1],
                'match_stats'    => ['shootout' => [
                    'winner_team_id' => $ai->id,
                    'scores'         => [$human->id => 3, $ai->id => 4],
                ]],
            ])
            ->assertRedirect();

        $this->assertSame($ai->id, (int) $match->fresh()->match_stats['shootout']['winner_team_id']);
    }

    public function test_shootout_is_dropped_when_it_does_not_apply(): void
    {
        [$save, $human, $ai, $match] = $this->knockoutSetup();

        // Pas d'égalité : une séance envoyée par le client est ignorée.
        $this->actingAs(User::find($save->user_id))
            ->post(route('game-saves.matches.finish', [$save, $match]), [
                'scoresByTeamId' => [$human->id => 2, $ai->id => 1],
                'match_stats'    => ['shootout' => ['winner_team_id' => $ai->id]],
            ])
            ->assertRedirect();

        $this->assertArrayNotHasKey('shootout', $match->fresh()->match_stats ?? []);
    }

    public function test_free_agent_signing_is_refused_in_world_cup(): void
    {
        [$save, $human] = $this->knockoutSetup();
        $player = GamePlayer::create([
            'game_save_id' => $save->id, 'firstname' => 'Libre', 'lastname' => 'Agent',
            'position' => 'MID', 'cost' => 10,
        ]);

        $this->actingAs(User::find($save->user_id))
            ->post(route('game-saves.free-agents.sign', [$save, $player]), [
                'team_id' => $human->id, 'matches_total' => 5,
            ])
            ->assertSessionHasErrors('player');

        $this->assertSame(0, $player->contracts()->count());
    }
}

<?php

namespace Tests\Feature;

use App\Services\PlayerMoraleSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class PlayerRecentChangesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_actual_training_gains_and_history_survive_multiple_weeks(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['is_controlled' => true]);
        $save->update(['controlled_game_team_id' => $team->id]);
        $save->setConfig(['training_gain_min' => 3, 'training_gain_max' => 3]);
        $player = $this->makePlayer($save, ['shot' => 50]);
        $this->makeContract($save, $team, $player);
        $this->actingAs(\App\Models\User::find($save->user_id));

        foreach ([1, 2] as $week) {
            $save->refresh()->update(['week' => $week]);
            $this->post(route('game-saves.training.store', $save), [
                'season' => 1, 'week' => $week,
                'trainings' => [['player_id' => $player->id, 'stat' => 'shot']],
            ])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame(50 + 3 * $week, (int) $player->fresh()->shot);
            $this->assertCount($week, $save->fresh()->state['training_history']);
        }

        $changes = app(PlayerMoraleSummary::class)->recentChanges($save->fresh(), [$player->id])
            ->get($player->id)->where('source', 'training')->values();
        $this->assertCount(2, $changes);
        $this->assertSame(2, $changes[0]['week']);
        $this->assertSame(53, $changes[0]['old']);
        $this->assertSame(56, $changes[0]['new']);
        $this->assertSame(3, $changes[0]['value']);
        $this->assertSame(1, $changes[1]['week']);
    }

    public function test_recent_changes_include_existing_training_and_match_gains_for_the_selected_player(): void
    {
        $save = $this->makeSave();
        $player = $this->makePlayer($save);
        $other = $this->makePlayer($save);
        $home = $this->makeTeam($save);
        $away = $this->makeTeam($save);
        $save->update(['state' => ['training' => [
            'season' => 1, 'week' => 1, 'entries' => [
                ['player_id' => $player->id, 'stat' => 'pass', 'gain' => 3],
                ['player_id' => $other->id, 'stat' => 'speed', 'gain' => 5],
            ],
        ]]]);
        $this->makeMatch($save, $home, $away, ['status' => 'played', 'match_stats' => [
            'progression' => [['player_id' => $player->id, 'gains' => [
                'shot' => ['old' => 30, 'new' => 31, 'gain' => 1],
            ]]],
        ]]);

        $changes = app(PlayerMoraleSummary::class)->recentChanges($save, [$player->id])->get($player->id);

        $this->assertCount(2, $changes);
        $this->assertSame(3, $changes->firstWhere('source', 'training')['value']);
        $this->assertSame('pass', $changes->firstWhere('source', 'training')['stat']);
        $this->assertSame(31, $changes->firstWhere('source', 'match')['new']);
        $this->assertFalse($changes->contains('player_id', $other->id));
    }
}

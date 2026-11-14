<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameDeclaration;
use App\Models\GameSaves\GamePromise;
use App\Services\Ai\AiCoachService;
use App\Services\Cards\BonusCardActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class AiCoachServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_ai_can_praise_and_promise_playing_time_without_acting_for_humans(): void
    {
        $save = $this->makeSave(['week' => 2]);
        [$human, $humanPlayers] = $this->makeTeamWithSquad($save, ['is_controlled' => true]);
        [$ai, $aiPlayers] = $this->makeTeamWithSquad($save);
        $save->update(['controlled_game_team_id' => $human->id]);
        $praised = $aiPlayers[0];
        $promised = $aiPlayers[1];
        $promised->update(['morale' => 50]);
        $this->makeMatch($save, $human, $ai, [
            'status' => 'played',
            'match_stats' => ['players' => [
                $humanPlayers[0]->id => ['duelsWon' => 4],
                $praised->id => ['duelsWon' => 3],
            ]],
        ]);
        $next = $this->makeMatch($save, $ai, $human, ['week' => 2]);

        $coach = app(AiCoachService::class);
        $coach->prepareForWeek($save, 1);
        $coach->prepareForWeek($save, 1);

        $this->assertSame([$praised->id], GameDeclaration::pluck('game_player_id')->all());
        $this->assertSame('praise', GameDeclaration::first()->type);
        $this->assertSame([$promised->id], GamePromise::pluck('game_player_id')->all());
        $this->assertSame('playing_time', GamePromise::first()->type);
        $this->assertSame([
            $praised->id => ['attack' => 2, 'defense' => 2, 'speed' => 2],
        ], app(BonusCardActivationService::class)->getMatchStatModifiers($save, $ai->id, $next->id)['players']);
    }
}

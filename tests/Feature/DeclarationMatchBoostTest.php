<?php

namespace Tests\Feature;

use App\Services\BonusCardActivationService;
use App\Services\DeclarationService;
use App\Services\MatchSimulator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class DeclarationMatchBoostTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_praise_can_penalize_only_the_next_match_after_a_poor_performance(): void
    {
        $save = $this->makeSave(['week' => 2]);
        [$team, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true]);
        [$opponent] = $this->makeTeamWithSquad($save);
        $save->update(['controlled_game_team_id' => $team->id]);
        $save->setConfig(['bonus_cards_enabled' => false]);
        [$penalized, $unaffected, $absent] = $players->values()->all();
        $this->makeMatch($save, $team, $opponent, [
            'status' => 'played',
            'match_stats' => ['players' => [
                $penalized->id => ['duelsWon' => 1, 'duelsLost' => 2],
                $unaffected->id => ['duelsWon' => 1, 'duelsLost' => 2],
            ]],
        ]);
        $next = $this->makeMatch($save, $opponent, $team, ['week' => 2]);
        $later = $this->makeMatch($save, $team, $opponent, ['week' => 3]);

        $service = new class extends DeclarationService {
            private array $rolls = [25, 26];

            protected function roll(): int
            {
                return array_shift($this->rolls);
            }
        };

        $this->assertSame('match_malus', $service->declare($save, $penalized, 'praise', $team->id)->outcome);
        $this->assertSame('well_received', $service->declare($save, $unaffected, 'praise', $team->id)->outcome);
        $this->assertSame('well_received', $service->declare($save, $absent, 'praise', $team->id)->outcome);
        $this->assertEquals(60, $penalized->fresh()->morale);

        $modifiers = app(BonusCardActivationService::class);
        $this->assertSame([
            $penalized->id => ['attack' => -2, 'defense' => -2, 'speed' => -2],
        ], $modifiers->getMatchStatModifiers($save, $team->id, $next->id)['players']);
        $this->assertSame([], $modifiers->getMatchStatModifiers($save, $team->id, $later->id)['players']);

        $response = $this->actingAs($save->user)->get(route('game-saves.match', $save))->assertOk();
        $lineup = $response->viewData('page')['props']['engineConfig']['teams']['internal']['players'];
        $this->assertSame(48, collect($lineup)->firstWhere('id', $penalized->id)['stats']['attack']);
        $this->assertEquals(50, $penalized->fresh()->attack);

        app(MatchSimulator::class)->simulateMatchesCollection(collect([$next]), $save);
        $this->assertSame([], $modifiers->getMatchStatModifiers($save->fresh(), $team->id, $next->id)['players']);
    }

    public function test_criticism_can_cost_morale_and_boost_only_the_next_match(): void
    {
        $save = $this->makeSave();
        [$team, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true]);
        [$opponent] = $this->makeTeamWithSquad($save);
        $save->update(['controlled_game_team_id' => $team->id]);
        $save->setConfig(['bonus_cards_enabled' => false]);
        $first = $this->makeMatch($save, $team, $opponent);
        $second = $this->makeMatch($save, $opponent, $team, ['week' => 2]);
        [$boosted, $upset] = $players->values()->all();

        $service = new class extends DeclarationService {
            private array $rolls = [60, 61];

            protected function roll(): int
            {
                return array_shift($this->rolls);
            }
        };

        $this->assertSame('match_boost', $service->declare($save, $boosted, 'criticize', $team->id)->outcome);
        $this->assertSame('backfired', $service->declare($save, $upset, 'criticize', $team->id)->outcome);
        $this->assertEquals(57, $boosted->fresh()->morale);
        $this->assertEquals(55, $upset->fresh()->morale);

        $modifiers = app(BonusCardActivationService::class);
        $this->assertSame([], $modifiers->getMatchStatModifiers($save, $team->id, $second->id)['players']);
        $this->assertSame([
            $boosted->id => ['attack' => 3, 'defense' => 3, 'speed' => 3],
        ], $modifiers->getMatchStatModifiers($save, $team->id, $first->id)['players']);

        $response = $this->actingAs($save->user)->get(route('game-saves.match', $save))->assertOk();
        $lineup = $response->viewData('page')['props']['engineConfig']['teams']['internal']['players'];
        $this->assertSame(53, collect($lineup)->firstWhere('id', $boosted->id)['stats']['attack']);
        $this->assertEquals(50, $boosted->fresh()->attack);

        app(MatchSimulator::class)->simulateMatchesCollection(collect([$first]), $save);
        $this->assertSame([], $modifiers->getMatchStatModifiers($save->fresh(), $team->id, $first->id)['players']);
    }
}

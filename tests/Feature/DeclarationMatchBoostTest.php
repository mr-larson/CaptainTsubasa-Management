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

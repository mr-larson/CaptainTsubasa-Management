<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameSanction;
use App\Services\FoulAndInjuryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Cartons, suspensions et blessures (FoulAndInjuryService). On cible les
 * chemins déterministes : événements 'card' et 'injury' explicites, cumul des
 * jaunes, gating par la config, et déduplication des blessures. Les fautes
 * (probabilistes) ne sont testées que pour le gating.
 */
class FoulAndInjuryServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private FoulAndInjuryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FoulAndInjuryService::class);
    }

    public function test_yellow_card_creates_a_sanction(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $match = $this->makeMatch($save, $team, $team, ['week' => 1, 'status' => 'played']);

        $this->service->processMatchEvents($save, $match, [
            ['type' => 'card', 'player_id' => $player->id, 'card_type' => 'yellow'],
        ]);

        $sanction = GameSanction::where('game_player_id', $player->id)->first();
        $this->assertNotNull($sanction);
        $this->assertSame('yellow', $sanction->type);
        $this->assertSame(0, (int) $sanction->weeks_suspended);
        $this->assertSame(2, (int) $sanction->week_return); // week + 1
    }

    public function test_third_yellow_becomes_a_one_week_suspension(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $match = $this->makeMatch($save, $team, $team, ['week' => 1, 'status' => 'played']);

        // Trois jaunes sur le même joueur.
        for ($i = 0; $i < 3; $i++) {
            $this->service->processMatchEvents($save, $match, [
                ['type' => 'card', 'player_id' => $player->id, 'card_type' => 'yellow'],
            ]);
        }

        $third = GameSanction::where('game_player_id', $player->id)->orderByDesc('id')->first();
        $this->assertSame('double_yellow', $third->type);
        $this->assertSame(1, (int) $third->weeks_suspended);
        $this->assertSame(3, (int) $third->yellow_card_count);
    }

    public function test_third_yellow_does_not_suspend_when_disabled(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $save->setConfig(['suspension_on_3_yellows' => false]);
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $match = $this->makeMatch($save, $team, $team, ['week' => 1, 'status' => 'played']);

        for ($i = 0; $i < 3; $i++) {
            $this->service->processMatchEvents($save->fresh(), $match, [
                ['type' => 'card', 'player_id' => $player->id, 'card_type' => 'yellow'],
            ]);
        }

        $third = GameSanction::where('game_player_id', $player->id)->orderByDesc('id')->first();
        $this->assertSame('yellow', $third->type, 'Sans la règle, pas de suspension au 3e jaune.');
        $this->assertSame(0, (int) $third->weeks_suspended);
    }

    public function test_red_card_suspends_for_two_to_three_weeks(): void
    {
        $save = $this->makeSave(['week' => 2]);
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $match = $this->makeMatch($save, $team, $team, ['week' => 2, 'status' => 'played']);

        $this->service->processMatchEvents($save, $match, [
            ['type' => 'card', 'player_id' => $player->id, 'card_type' => 'red'],
        ]);

        $red = GameSanction::where('game_player_id', $player->id)->first();
        $this->assertSame('red', $red->type);
        $this->assertGreaterThanOrEqual(2, (int) $red->weeks_suspended);
        $this->assertLessThanOrEqual(3, (int) $red->weeks_suspended);
        $this->assertSame(2 + 1 + (int) $red->weeks_suspended, (int) $red->week_return);
    }

    public function test_red_card_is_ignored_when_suspension_disabled(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $save->setConfig(['suspension_on_3_yellows' => false]);
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $match = $this->makeMatch($save, $team, $team, ['week' => 1, 'status' => 'played']);

        $this->service->processMatchEvents($save->fresh(), $match, [
            ['type' => 'card', 'player_id' => $player->id, 'card_type' => 'red'],
        ]);

        $this->assertSame(0, GameSanction::where('game_player_id', $player->id)->count());
    }

    public function test_direct_injury_event_creates_injury_in_severity_range(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $match = $this->makeMatch($save, $team, $team, ['week' => 1, 'status' => 'played']);

        $this->service->processMatchEvents($save, $match, [
            ['type' => 'injury', 'player_id' => $player->id, 'severity' => 'moderate'],
        ]);

        $injury = GameInjury::where('game_player_id', $player->id)->first();
        $this->assertSame('moderate', $injury->severity);
        // moderate = 2..3 semaines.
        $this->assertGreaterThanOrEqual(2, (int) $injury->weeks_out);
        $this->assertLessThanOrEqual(3, (int) $injury->weeks_out);
        $this->assertSame(1 + 1 + (int) $injury->weeks_out, (int) $injury->week_return);
    }

    public function test_injury_events_are_skipped_when_disabled(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $save->setConfig(['injury_on_foul' => false]);
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $match = $this->makeMatch($save, $team, $team, ['week' => 1, 'status' => 'played']);

        $this->service->processMatchEvents($save->fresh(), $match, [
            ['type' => 'injury', 'player_id' => $player->id, 'severity' => 'severe'],
        ]);

        $this->assertSame(0, GameInjury::where('game_player_id', $player->id)->count());
    }

    public function test_already_injured_player_is_not_injured_again(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $match = $this->makeMatch($save, $team, $team, ['week' => 1, 'status' => 'played']);

        $events = [['type' => 'injury', 'player_id' => $player->id, 'severity' => 'severe']];
        $this->service->processMatchEvents($save, $match, $events);
        $this->service->processMatchEvents($save, $match, $events); // doublon

        $this->assertSame(1, GameInjury::where('game_player_id', $player->id)->count());
    }

    public function test_static_query_helpers_return_active_records(): void
    {
        $save = $this->makeSave(['week' => 5]);
        $team = $this->makeTeam($save);
        $injuredPlayer = $this->makePlayer($save);
        $suspendedPlayer = $this->makePlayer($save);
        $match = $this->makeMatch($save, $team, $team, ['week' => 5, 'status' => 'played']);

        // Blessure active (retour semaine 8) et suspension active (retour 7).
        GameInjury::create([
            'game_save_id' => $save->id, 'game_player_id' => $injuredPlayer->id, 'game_match_id' => $match->id,
            'severity' => 'moderate', 'weeks_out' => 2, 'week_injured' => 5, 'week_return' => 8,
        ]);
        GameSanction::create([
            'game_save_id' => $save->id, 'game_player_id' => $suspendedPlayer->id, 'game_match_id' => $match->id,
            'type' => 'red', 'weeks_suspended' => 2, 'week_match' => 5, 'week_return' => 7,
        ]);

        $this->assertCount(1, FoulAndInjuryService::activeInjuries($save->id, 5));
        $this->assertCount(1, FoulAndInjuryService::activeSuspensions($save->id, 5));
        // À la semaine 8, la blessure (retour 8) n'est plus active.
        $this->assertCount(0, FoulAndInjuryService::activeInjuries($save->id, 8));
    }
}

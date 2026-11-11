<?php

namespace Tests\Feature;

use App\Enums\TeamStyle;
use App\Models\GameSaves\GameInjury;
use App\Services\AITrainingService;
use App\Services\AiLineUpService;
use App\Services\StaminaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * IA de composition (AiLineUpService::adjustLineupsForWeek) : remplacement des
 * titulaires indisponibles (blessés/suspendus) et rotation des titulaires
 * fatigués selon la philosophie. N'intervient pas sur les équipes humaines.
 */
class AiLineUpServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private AiLineUpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AiLineUpService::class);
    }

    public function test_ai_assigns_a_captain_and_keeps_the_choice_on_repeated_preparation(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save);
        $ordinary = $this->makeContract($save, $team, $this->makePlayer($save));
        $best = $this->makeContract($save, $team, $this->makePlayer($save, ['pass' => 90]));

        $this->service->adjustLineupsForWeek($save);
        $this->service->adjustLineupsForWeek($save->fresh());

        $this->assertTrue($best->fresh()->is_captain);
        $this->assertFalse($ordinary->fresh()->is_captain);
        $this->assertSame(1, $team->contracts()->where('is_captain', true)->count());
    }

    public function test_ai_preserves_an_available_starting_captain_even_with_a_stronger_teammate(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save);
        $captain = $this->makeContract($save, $team, $this->makePlayer($save), ['is_captain' => true]);
        $stronger = $this->makeContract($save, $team, $this->makePlayer($save, ['pass' => 95]));

        $this->service->adjustLineupsForWeek($save);

        $this->assertTrue($captain->fresh()->is_captain);
        $this->assertFalse($stronger->fresh()->is_captain);
    }

    public function test_ai_reassigns_captain_after_fatigue_rotation(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['management_philosophy' => TeamStyle::PHILOSOPHY_ECONOMIST]);
        $captain = $this->makeContract($save, $team, $this->makePlayer($save, ['stamina' => 30]), ['is_captain' => true]);
        $replacement = $this->makeContract($save, $team, $this->makePlayer($save), ['is_starter' => false]);

        $this->service->adjustLineupsForWeek($save);

        $this->assertFalse($captain->fresh()->is_starter);
        $this->assertFalse($captain->fresh()->is_captain);
        $this->assertTrue($replacement->fresh()->is_captain);
    }

    public function test_ai_does_not_keep_an_injured_captain_even_without_a_replacement_for_his_position(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save);
        $injured = $this->makePlayer($save);
        $captain = $this->makeContract($save, $team, $injured, ['is_captain' => true]);
        $available = $this->makeContract($save, $team, $this->makePlayer($save));
        GameInjury::create([
            'game_save_id' => $save->id, 'game_player_id' => $injured->id,
            'severity' => 'moderate', 'weeks_out' => 2, 'week_injured' => 1, 'week_return' => 3,
        ]);

        $this->service->adjustLineupsForWeek($save);

        $this->assertFalse($captain->fresh()->is_captain);
        $this->assertTrue($available->fresh()->is_captain);
    }

    public function test_ai_captain_assignment_does_not_touch_human_teams(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $contract = $this->makeContract($save, $team, $this->makePlayer($save));

        $this->service->adjustLineupsForWeek($save);

        $this->assertFalse($contract->fresh()->is_captain);
    }

    public function test_injured_starter_is_replaced_by_available_sub(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save); // IA (non contrôlée)

        // Effectif frais (stamina 100) → pas de rotation par fatigue.
        $starter = $this->makePlayer($save, ['position' => 'ATT', 'stamina' => 100]);
        $sub     = $this->makePlayer($save, ['position' => 'ATT', 'stamina' => 100]);
        $starterContract = $this->makeContract($save, $team, $starter, ['is_starter' => true]);
        $subContract     = $this->makeContract($save, $team, $sub, ['is_starter' => false]);

        // Le titulaire est blessé (retour semaine 3).
        GameInjury::create([
            'game_save_id' => $save->id, 'game_player_id' => $starter->id,
            'severity' => 'moderate', 'weeks_out' => 2, 'week_injured' => 1, 'week_return' => 3,
        ]);

        $this->service->adjustLineupsForWeek($save);

        $this->assertFalse((bool) $starterContract->fresh()->is_starter, 'Le blessé sort du onze.');
        $this->assertTrue((bool) $subContract->fresh()->is_starter, 'Le remplaçant entre.');
    }

    public function test_tired_starter_is_rotated_under_economist_philosophy(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save, ['management_philosophy' => TeamStyle::PHILOSOPHY_ECONOMIST]);

        // Économe : seuil 65. Titulaire fatigué, remplaçant frais, même poste.
        $tired = $this->makePlayer($save, ['position' => 'MID', 'stamina' => 40]);
        $fresh = $this->makePlayer($save, ['position' => 'MID', 'stamina' => 100]);
        $tiredContract = $this->makeContract($save, $team, $tired, ['is_starter' => true]);
        $freshContract = $this->makeContract($save, $team, $fresh, ['is_starter' => false]);

        $this->service->adjustLineupsForWeek($save);

        $this->assertFalse((bool) $tiredContract->fresh()->is_starter);
        $this->assertTrue((bool) $freshContract->fresh()->is_starter);
    }

    public function test_collective_alternates_close_keepers_and_trains_the_one_who_rested(): void
    {
        $save = $this->makeSave();
        $save->setConfig(['training_max_per_week' => 1]);
        $team = $this->makeTeam($save, [
            'management_philosophy' => TeamStyle::PHILOSOPHY_COLLECTIVE,
            'tactical_style' => TeamStyle::TACTICAL_DEFENSIVE,
        ]);
        $opponent = $this->makeTeam($save, ['is_controlled' => true]);
        $first = $this->makePlayer($save, ['position' => 'Goalkeeper', 'stamina' => 95, 'hand_save' => 35, 'punch_save' => 30]);
        $second = $this->makePlayer($save, ['position' => 'Goalkeeper', 'stamina' => 100, 'hand_save' => 35, 'punch_save' => 30]);
        $firstContract = $this->makeContract($save, $team, $first);
        $secondContract = $this->makeContract($save, $team, $second, ['is_starter' => false]);
        $save->update(['state' => array_replace($save->state, ['lineup' => [$team->id => ['slots' => [1 => $first->id]]]])]);

        $this->service->adjustLineupsForWeek($save);
        $this->service->adjustLineupsForWeek($save->fresh());
        $this->assertTrue((bool) $secondContract->fresh()->is_starter);
        $this->assertSame($second->id, $save->fresh()->state['lineup'][$team->id]['slots'][1]);

        $this->makeMatch($save, $team, $opponent, [
            'status' => 'played',
            'match_stats' => ['playtime' => [$second->id => 30, $first->id => 0], 'players' => []],
        ]);
        app(AITrainingService::class)->trainForWeek($save->fresh());
        $this->assertSame($first->id, $save->fresh()->state['ai_training_history'][0]['player_id']);
        StaminaService::applyAfterWeek($save, 1);

        $save->refresh()->update(['week' => 2]);
        $this->service->adjustLineupsForWeek($save);
        $this->assertTrue((bool) $firstContract->fresh()->is_starter);
    }

    public function test_field_rotation_uses_the_slot_role_of_a_player_at_a_secondary_position(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, [
            'formation' => '4-2-1-3',
            'management_philosophy' => TeamStyle::PHILOSOPHY_ECONOMIST,
        ]);
        $starter = $this->makePlayer($save, ['position' => 'Forward', 'secondary_positions' => ['Midfielder'], 'stamina' => 90]);
        $midfielder = $this->makePlayer($save, ['position' => 'Midfielder']);
        $forward = $this->makePlayer($save, ['position' => 'Forward']);
        $this->makeContract($save, $team, $starter);
        $this->makeContract($save, $team, $midfielder, ['is_starter' => false]);
        $this->makeContract($save, $team, $forward, ['is_starter' => false]);
        $save->update(['state' => ['lineup' => [$team->id => ['slots' => [6 => $starter->id]]]]]);

        $this->service->adjustLineupsForWeek($save);

        $this->assertSame($midfielder->id, $save->fresh()->state['lineup'][$team->id]['slots'][6]);
        $this->assertSame('MID', $save->fresh()->state['ai_rotation'][$team->id]['resting_roles'][$starter->id]);
    }

    public function test_interactive_match_starts_with_the_fresh_replacement_in_the_saved_slot(): void
    {
        $save = $this->makeSave();
        [$human] = $this->makeTeamWithSquad($save, ['is_controlled' => true]);
        [$ai, $players] = $this->makeTeamWithSquad($save, [
            'formation' => '4-2-1-3',
            'management_philosophy' => TeamStyle::PHILOSOPHY_ECONOMIST,
            'tactical_style' => TeamStyle::TACTICAL_COUNTER,
        ], 12);
        $tired = $players[5];
        $fresh = $players[11];
        $tired->update(['stamina' => 30]);
        $slots = $players->take(11)->mapWithKeys(fn ($player, $index) => [$index + 1 => $player->id])->all();
        $save->update([
            'controlled_game_team_id' => $human->id,
            'state' => ['lineup' => [$ai->id => ['slots' => $slots]]],
        ]);
        $this->makeMatch($save, $human, $ai);

        $this->actingAs($save->user);
        $preview = $this->get(route('game-saves.Play', $save))->assertOk();
        $previewTeam = collect($preview->viewData('page')['props']['teams'])->firstWhere('id', $ai->id);
        $previewSlots = $preview->viewData('page')['props']['gameSave']['state']['lineup'][$ai->id]['slots'];
        $this->assertSame($fresh->id, $previewSlots[6]);
        $this->assertTrue(collect($previewTeam['contracts'])->firstWhere('game_player_id', $fresh->id)['is_starter']);

        $response = $this->get(route('game-saves.match', $save))->assertOk();
        $team = $response->viewData('page')['props']['engineConfig']['teams']['external'];
        $this->assertSame('counter', $team['tacticalStyle']);
        $this->assertSame('economist', $team['managementPhilosophy']);
        $this->assertSame($fresh->id, $team['players'][5]['id']);
        $this->assertSame($fresh->id, $save->fresh()->state['lineup'][$ai->id]['slots'][6]);
        $this->assertFalse((bool) $tired->contracts()->first()->is_starter);
    }

    public function test_ai_changes_formation_to_cover_a_shortage_of_defenders(): void
    {
        $save = $this->makeSave();
        [$human] = $this->makeTeamWithSquad($save, ['is_controlled' => true]);
        [$ai, $players] = $this->makeTeamWithSquad($save, [
            'formation' => '5-2-2-1',
            'tactical_style' => TeamStyle::TACTICAL_POSSESSION,
        ], 13);
        // Deux attaquants et cinq milieux sont prêts, mais un des quatre défenseurs est blessé.
        $players[10]->update(['position' => 'MID']);
        $injured = $players[4];
        GameInjury::create([
            'game_save_id' => $save->id, 'game_player_id' => $injured->id,
            'severity' => 'moderate', 'weeks_out' => 2, 'week_injured' => 1, 'week_return' => 3,
        ]);
        $save->update(['controlled_game_team_id' => $human->id]);
        $this->makeMatch($save, $human, $ai);

        $this->actingAs($save->user);
        $preview = $this->get(route('game-saves.Play', $save))->assertOk();
        $previewTeam = collect($preview->viewData('page')['props']['teams'])->firstWhere('id', $ai->id);
        $this->assertStringStartsWith('3-', $previewTeam['formation']);
        $response = $this->get(route('game-saves.match', $save))->assertOk();
        $team = $response->viewData('page')['props']['engineConfig']['teams']['external'];
        $formation = $ai->fresh()->formation;
        $lineup = $save->fresh()->state['lineup'][$ai->id];

        $this->assertStringStartsWith('3-', $formation);
        $this->assertSame($formation, $team['formation']);
        $this->assertSame($previewTeam['formation'], $team['formation']);
        $this->assertSame($formation, $lineup['formation']);
        $this->assertCount(11, array_unique($lineup['slots']));
        $this->assertNotContains($injured->id, $lineup['slots']);
        $this->assertSame(array_values($lineup['slots']), array_column(array_slice($team['players'], 0, 11), 'id'));
        $this->service->adjustLineupsForWeek($save->fresh());
        $this->assertSame($formation, $ai->fresh()->formation);
        $this->assertSame($lineup, $save->fresh()->state['lineup'][$ai->id]);
    }

    public function test_ai_repairs_misplaced_players_without_changing_an_adequate_formation(): void
    {
        $save = $this->makeSave();
        [$ai, $players] = $this->makeTeamWithSquad($save, ['formation' => '4-2-1-3'], 12);
        $keeper = $players[0];
        $defender = $players[1];
        $midfielder = $players[5];
        $slots = $players->take(11)->mapWithKeys(fn ($player, $index) => [$index + 1 => $player->id])->all();
        $slots[1] = $midfielder->id;
        $slots[2] = $keeper->id;
        $slots[6] = $defender->id;
        $save->update(['state' => ['lineup' => [$ai->id => ['slots' => $slots]]]]);

        $this->service->adjustLineupsForWeek($save);

        $lineup = $save->fresh()->state['lineup'][$ai->id];
        $this->assertSame('4-2-1-3', $ai->fresh()->formation);
        $this->assertSame($keeper->id, $lineup['slots'][1]);
        $this->assertContains($defender->id, array_slice($lineup['slots'], 1, 4));
        $this->assertContains($midfielder->id, array_slice($lineup['slots'], 5, 3));
    }

    public function test_no_rotation_when_squad_is_fresh(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save, ['management_philosophy' => TeamStyle::PHILOSOPHY_ECONOMIST]);

        $starter = $this->makePlayer($save, ['position' => 'MID', 'stamina' => 100]);
        $sub     = $this->makePlayer($save, ['position' => 'MID', 'stamina' => 100]);
        $starterContract = $this->makeContract($save, $team, $starter, ['is_starter' => true]);
        $subContract     = $this->makeContract($save, $team, $sub, ['is_starter' => false]);

        $this->service->adjustLineupsForWeek($save);

        $this->assertTrue((bool) $starterContract->fresh()->is_starter, 'Aucune raison de faire tourner.');
        $this->assertFalse((bool) $subContract->fresh()->is_starter);
    }

    public function test_human_team_is_left_untouched(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $human = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $save->controlled_game_team_id = $human->id;
        $save->save();

        $starter = $this->makePlayer($save, ['position' => 'ATT', 'stamina' => 100]);
        $sub     = $this->makePlayer($save, ['position' => 'ATT', 'stamina' => 100]);
        $starterContract = $this->makeContract($save, $human, $starter, ['is_starter' => true]);
        $this->makeContract($save, $human, $sub, ['is_starter' => false]);

        GameInjury::create([
            'game_save_id' => $save->id, 'game_player_id' => $starter->id,
            'severity' => 'moderate', 'weeks_out' => 2, 'week_injured' => 1, 'week_return' => 3,
        ]);

        $this->service->adjustLineupsForWeek($save->fresh());

        // L'IA ne touche pas au onze d'une équipe humaine (géré par le joueur).
        $this->assertTrue((bool) $starterContract->fresh()->is_starter);
    }
}

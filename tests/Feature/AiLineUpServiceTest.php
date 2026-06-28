<?php

namespace Tests\Feature;

use App\Enums\TeamStyle;
use App\Models\GameSaves\GameInjury;
use App\Services\AiLineUpService;
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

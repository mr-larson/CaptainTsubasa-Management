<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GamePositionTraining;
use App\Services\Training\PositionTrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Formation à un nouveau poste (PositionTrainingService) : durée selon la
 * distance entre lignes, indisponibilité, quota/coût partagés avec
 * l'entraînement, et acquisition du poste secondaire à terme.
 */
class PositionTrainingTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private PositionTrainingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PositionTrainingService::class);
    }

    public function test_duration_depends_on_distance_between_lines(): void
    {
        $this->assertSame(1, PositionTrainingService::durationFor(['Defender'], 'Midfielder'));
        $this->assertSame(1, PositionTrainingService::durationFor(['Forward'], 'Midfielder'));
        $this->assertSame(2, PositionTrainingService::durationFor(['Defender'], 'Forward'));
        // Distance depuis le poste connu le plus proche (secondaire compris).
        $this->assertSame(1, PositionTrainingService::durationFor(['Defender', 'Midfielder'], 'Forward'));
        // Joueur de champ → gardien.
        $this->assertSame(3, PositionTrainingService::durationFor(['Midfielder'], 'Goalkeeper'));
        // Gardien → défenseur, puis au-delà.
        $this->assertSame(2, PositionTrainingService::durationFor(['Goalkeeper'], 'Defender'));
        $this->assertSame(3, PositionTrainingService::durationFor(['Goalkeeper'], 'Midfielder'));
        $this->assertSame(3, PositionTrainingService::durationFor(['Goalkeeper'], 'Forward'));
    }

    public function test_start_makes_player_unavailable_and_uses_a_training_slot(): void
    {
        [$save, $team, $player] = $this->world(['position' => 'Defender'], budget: 1000);

        $training = $this->service->start($save, $player->id, 'Forward');

        $this->assertSame(1, $training->week_start);
        $this->assertSame(3, $training->week_return);
        $this->assertSame([$player->id], GamePositionTraining::unavailablePlayerIds($save->id, 1));
        $this->assertSame([$player->id], GamePositionTraining::unavailablePlayerIds($save->id, 2));
        $this->assertSame([], GamePositionTraining::unavailablePlayerIds($save->id, 3));

        // Coût d'une séance (config par défaut) et quota consommé.
        $this->assertSame(800, (int) $team->fresh()->budget);
        $entries = $save->fresh()->state['training']['entries'];
        $this->assertCount(1, $entries);
        $this->assertSame('position', $entries[0]['stat']);
    }

    public function test_position_is_acquired_when_training_completes(): void
    {
        [$save, , $player] = $this->world(['position' => 'Defender', 'secondary_positions' => []]);

        $this->service->start($save, $player->id, 'Midfielder');

        $this->service->completeDue($save, 1);
        $this->assertSame([], (array) $player->fresh()->secondary_positions);

        $this->service->completeDue($save, 2);
        $this->assertSame(['Midfielder'], (array) $player->fresh()->secondary_positions);
        $this->assertSame([], GamePositionTraining::unavailablePlayerIds($save->id, 2));
    }

    public function test_known_position_is_refused(): void
    {
        [$save, , $player] = $this->world(['position' => 'Defender', 'secondary_positions' => ['Midfielder']]);

        $this->expectException(ValidationException::class);
        $this->service->start($save, $player->id, 'Midfielder');
    }

    public function test_injured_player_cannot_start_a_training(): void
    {
        [$save, , $player] = $this->world(['position' => 'Defender']);
        GameInjury::create([
            'game_save_id' => $save->id, 'game_player_id' => $player->id,
            'severity' => 'light', 'weeks_out' => 1, 'week_injured' => 1, 'week_return' => 2,
        ]);

        $this->expectException(ValidationException::class);
        $this->service->start($save, $player->id, 'Midfielder');
    }

    public function test_route_starts_a_training_for_the_owner(): void
    {
        [$save, , $player] = $this->world(['position' => 'Defender'], budget: 1000);

        $this->actingAs($save->user)
            ->post(route('game-saves.position-training.store', $save), [
                'player_id' => $player->id,
                'position'  => 'Midfielder',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('game_position_trainings', [
            'game_player_id'  => $player->id,
            'target_position' => 'Midfielder',
            'week_return'     => 2,
        ]);
    }

    public function test_special_training_charges_once_and_unlocks_the_move_after_one_week(): void
    {
        $pass = ['key' => 'existing_pass', 'mode' => 'attack', 'base_action' => 'pass', 'label' => 'Passe'];
        [$save, $team, $player] = $this->world(['position' => 'Midfielder', 'special_moves' => [$pass]], budget: 1000);
        $request = [
            'player_id' => $player->id,
            'move_key' => 'training_curled_cross',
            'season' => 1,
            'week' => 1,
        ];
        $url = route('game-saves.special-training.store', $save);

        $this->actingAs($save->user)->post($url, $request)->assertSessionHasNoErrors();
        $this->post($url, $request)->assertSessionHasErrors('special_training');

        $this->assertSame(700, (int) $team->fresh()->budget);
        $this->assertSame([$player->id], GamePositionTraining::unavailablePlayerIds($save->id, 1));
        $this->assertEquals([$pass], $player->fresh()->special_moves);
        $this->assertSame('special_move', $save->fresh()->state['training']['entries'][0]['stat']);
        $this->assertDatabaseHas('game_position_trainings', ['game_player_id' => $player->id, 'cost' => 300]);

        $this->service->completeDue($save, 2);
        $this->service->completeDue($save, 2);
        $this->assertSame([], GamePositionTraining::unavailablePlayerIds($save->id, 2));
        $this->assertSame(['existing_pass', 'training_curled_cross'], array_column($player->fresh()->special_moves, 'key'));

        $save->update(['week' => 2]);
        $this->post($url, array_replace($request, ['week' => 2]))->assertSessionHasErrors('special_training');
        $this->assertSame(700, (int) $team->fresh()->budget);
    }

    public function test_special_training_uses_the_configured_cost(): void
    {
        [$save, $team, $player] = $this->world(['position' => 'Midfielder'], budget: 500);
        $save->setConfig(['special_training_cost' => 450]);

        $this->actingAs($save->user)->post(route('game-saves.special-training.store', $save), [
            'player_id' => $player->id,
            'move_key' => 'training_curled_cross',
            'season' => 1,
            'week' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame(50, (int) $team->fresh()->budget);
        $this->assertDatabaseHas('game_position_trainings', ['game_player_id' => $player->id, 'cost' => 450]);
    }

    /** Save en semaine 1 avec une équipe contrôlée et un joueur sous contrat. */
    private function world(array $playerAttrs, int $budget = 5000): array
    {
        $save = $this->makeSave(['week' => 1, 'season' => 1]);
        $team = $this->makeTeam($save, ['is_controlled' => true, 'budget' => $budget]);
        $save->controlled_game_team_id = $team->id;
        $save->save();

        $player = $this->makePlayer($save, $playerAttrs);
        $this->makeContract($save, $team, $player);

        return [$save, $team, $player];
    }
}

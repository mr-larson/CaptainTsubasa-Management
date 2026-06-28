<?php

namespace Tests\Feature;

use App\Services\TrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Entraînement (TrainingService::applyTrainings) : gains de stats bornés, coût
 * en endurance, et garde-fous (quota hebdo, doublons, joueur hors effectif,
 * endurance insuffisante). Écrit dans state['training'].
 */
class TrainingServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private TrainingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TrainingService::class);
    }

    public function test_training_increases_stat_and_costs_stamina(): void
    {
        $save   = $this->makeSave();
        $team   = $this->makeTeam($save, ['is_controlled' => true]);
        $save->controlled_game_team_id = $team->id;
        $save->save();

        $player = $this->makePlayer($save, ['shot' => 50, 'stamina' => 100]);
        $this->makeContract($save, $team, $player);

        $applied = $this->service->applyTrainings($save, 1, 1, [
            ['player_id' => $player->id, 'stat' => 'shot'],
        ]);

        $player->refresh();
        // Gain aléatoire 1..5, borné à 100.
        $this->assertGreaterThan(50, (int) $player->shot);
        $this->assertLessThanOrEqual(55, (int) $player->shot);
        // Coût d'endurance fixe (config par défaut = 2).
        $this->assertSame(98, (int) $player->stamina);

        // Trace dans le state.
        $this->assertCount(1, $applied);
        $this->assertSame(1, $save->fresh()->state['training']['season']);
        $this->assertCount(1, $save->fresh()->state['training']['entries']);
    }

    public function test_stat_gain_is_clamped_to_maximum(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['is_controlled' => true]);
        $save->controlled_game_team_id = $team->id;
        $save->save();

        $player = $this->makePlayer($save, ['shot' => 100, 'stamina' => 100]);
        $this->makeContract($save, $team, $player);

        $this->service->applyTrainings($save, 1, 1, [
            ['player_id' => $player->id, 'stat' => 'shot'],
        ]);

        $this->assertSame(100, (int) $player->fresh()->shot, 'La stat ne dépasse pas le plafond.');
    }

    public function test_weekly_training_quota_is_enforced(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['is_controlled' => true]);
        $save->controlled_game_team_id = $team->id;
        $save->save();

        // 4 joueurs, quota par défaut = 3 entraînements/semaine.
        $players = collect(range(1, 4))->map(fn () => $this->makePlayer($save, ['stamina' => 100]));
        $players->each(fn ($p) => $this->makeContract($save, $team, $p));

        $this->expectException(ValidationException::class);
        $this->service->applyTrainings($save, 1, 1,
            $players->map(fn ($p) => ['player_id' => $p->id, 'stat' => 'shot'])->all()
        );
    }

    public function test_same_player_cannot_be_trained_twice_in_one_request(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['is_controlled' => true]);
        $save->controlled_game_team_id = $team->id;
        $save->save();

        $player = $this->makePlayer($save, ['stamina' => 100]);
        $this->makeContract($save, $team, $player);

        $this->expectException(ValidationException::class);
        $this->service->applyTrainings($save, 1, 1, [
            ['player_id' => $player->id, 'stat' => 'shot'],
            ['player_id' => $player->id, 'stat' => 'pass'],
        ]);
    }

    public function test_player_outside_controlled_team_is_rejected(): void
    {
        $save = $this->makeSave();
        $controlled = $this->makeTeam($save, ['is_controlled' => true]);
        $other      = $this->makeTeam($save);
        $save->controlled_game_team_id = $controlled->id;
        $save->save();

        // Joueur sous contrat avec une AUTRE équipe.
        $player = $this->makePlayer($save, ['stamina' => 100]);
        $this->makeContract($save, $other, $player);

        $this->expectException(ValidationException::class);
        $this->service->applyTrainings($save, 1, 1, [
            ['player_id' => $player->id, 'stat' => 'shot'],
        ]);
    }

    public function test_training_deducts_money_from_budget(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['is_controlled' => true, 'budget' => 1000]);
        $save->controlled_game_team_id = $team->id;
        $save->save();

        $player = $this->makePlayer($save, ['stamina' => 100]);
        $this->makeContract($save, $team, $player);

        // Coût par défaut = 200 € par séance.
        $applied = $this->service->applyTrainings($save, 1, 1, [
            ['player_id' => $player->id, 'stat' => 'shot'],
        ]);

        $this->assertSame(800, (int) $team->fresh()->budget, 'Le budget est débité du coût de la séance.');
        $this->assertSame(200, (int) $applied[0]['cost']);
    }

    public function test_training_rejected_when_budget_insufficient(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['is_controlled' => true, 'budget' => 100]); // < 200
        $save->controlled_game_team_id = $team->id;
        $save->save();

        $player = $this->makePlayer($save, ['stamina' => 100, 'shot' => 50]);
        $this->makeContract($save, $team, $player);

        try {
            $this->service->applyTrainings($save, 1, 1, [
                ['player_id' => $player->id, 'stat' => 'shot'],
            ]);
            $this->fail('Un budget insuffisant aurait dû lever une ValidationException.');
        } catch (ValidationException $e) {
            // Rien n'est appliqué : budget et stat inchangés.
            $this->assertSame(100, (int) $team->fresh()->budget);
            $this->assertSame(50, (int) $player->fresh()->shot);
        }
    }

    public function test_player_below_minimum_stamina_cannot_train(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['is_controlled' => true]);
        $save->controlled_game_team_id = $team->id;
        $save->save();

        $player = $this->makePlayer($save, ['stamina' => 5]); // < min (10)
        $this->makeContract($save, $team, $player);

        $this->expectException(ValidationException::class);
        $this->service->applyTrainings($save, 1, 1, [
            ['player_id' => $player->id, 'stat' => 'shot'],
        ]);
    }
}

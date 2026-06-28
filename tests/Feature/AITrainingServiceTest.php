<?php

namespace Tests\Feature;

use App\Models\GameSaves\GamePlayer;
use App\Services\AITrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * IA d'entraînement (AITrainingService::trainForWeek). La sélection est
 * aléatoire : on teste des invariants stables — la semaine est marquée traitée
 * (anti double-entraînement), l'historique est alimenté, et les joueurs déjà
 * entraînés à la main sont exclus.
 */
class AITrainingServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private AITrainingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AITrainingService::class);
    }

    public function test_ai_trains_squad_and_marks_week_processed(): void
    {
        $save = $this->makeSave(['week' => 1, 'season' => 1]);
        [$team] = $this->makeTeamWithSquad($save); // 11 joueurs, stamina 100

        $staminaBefore = (int) GamePlayer::where('game_save_id', $save->id)->sum('stamina');

        $this->service->trainForWeek($save);
        $save->refresh();

        $this->assertSame(1, (int) $save->state['ai_training_week']);
        $this->assertNotEmpty($save->state['ai_training_history']);

        // Entraîner coûte de l'endurance → la somme baisse (au moins un joueur entraîné).
        $staminaAfter = (int) GamePlayer::where('game_save_id', $save->id)->sum('stamina');
        $this->assertLessThan($staminaBefore, $staminaAfter);
    }

    public function test_training_is_idempotent_within_the_same_week(): void
    {
        $save = $this->makeSave(['week' => 1, 'season' => 1]);
        $this->makeTeamWithSquad($save);

        $this->service->trainForWeek($save);
        $historyCount = count($save->fresh()->state['ai_training_history']);

        // Second appel la même semaine : aucun nouvel entraînement.
        $this->service->trainForWeek($save->fresh());

        $this->assertCount($historyCount, $save->fresh()->state['ai_training_history']);
    }

    public function test_manually_trained_player_is_not_auto_trained(): void
    {
        // L'exclusion des joueurs entraînés à la main concerne l'auto-entraînement
        // doux de l'équipe HUMAINE (les équipes IA, elles, entraînent librement).
        $save = $this->makeSave(['week' => 1, 'season' => 1]);
        $team = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $save->controlled_game_team_id = $team->id;
        $save->save();

        // Une seule recrue, déjà entraînée à la main cette semaine.
        $player = $this->makePlayer($save, ['stamina' => 100, 'shot' => 50]);
        $this->makeContract($save, $team, $player);

        $save->state = ['training' => [
            'season' => 1, 'week' => 1,
            'entries' => [['player_id' => $player->id, 'stat' => 'shot', 'gain' => 2, 'stamina_cost' => 2]],
        ]];
        $save->save();

        $this->service->trainForWeek($save);

        // Exclu du pool IA → endurance intacte (pas de second entraînement).
        $this->assertSame(100, (int) $player->fresh()->stamina);
    }
}

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

    public function test_ai_pays_for_each_training_session(): void
    {
        $save = $this->makeSave(['week' => 1, 'season' => 1]);
        // Équipe IA (non contrôlée), budget connu.
        [$team] = $this->makeTeamWithSquad($save, ['budget' => 100000]);

        $this->service->trainForWeek($save);
        $save->refresh();

        $sessions = collect($save->state['ai_training_history'])
            ->where('team_id', $team->id)
            ->count();

        // Coût par défaut = 200 € : le budget est débité d'exactement 200 € par séance.
        $this->assertGreaterThan(0, $sessions);
        $this->assertSame(100000 - $sessions * 200, (int) $team->fresh()->budget);
    }

    public function test_ai_skips_training_when_broke(): void
    {
        $save = $this->makeSave(['week' => 1, 'season' => 1]);
        [$team] = $this->makeTeamWithSquad($save, ['budget' => 100]); // < 200

        $staminaBefore = (int) GamePlayer::where('game_save_id', $save->id)->sum('stamina');

        $this->service->trainForWeek($save);
        $save->refresh();

        // Sans budget, aucune séance IA : budget intact, endurance intacte, historique vide.
        $this->assertSame(100, (int) $team->fresh()->budget);
        $this->assertSame($staminaBefore, (int) GamePlayer::where('game_save_id', $save->id)->sum('stamina'));
        $this->assertEmpty($save->state['ai_training_history']);
    }

    public function test_human_team_is_never_auto_trained(): void
    {
        // L'auto-entraînement est réservé aux équipes IA : une équipe humaine
        // n'est jamais auto-entraînée, même pour un joueur NON entraîné à la main.
        $save = $this->makeSave(['week' => 1, 'season' => 1]);
        $team = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $save->controlled_game_team_id = $team->id;
        $save->save();

        // Joueur sans aucun entraînement manuel enregistré cette semaine.
        $player = $this->makePlayer($save, ['stamina' => 100, 'shot' => 50]);
        $this->makeContract($save, $team, $player);

        $this->service->trainForWeek($save);

        $fresh = $player->fresh();

        // Aucune progression ni perte d'endurance : l'équipe humaine est ignorée.
        $this->assertSame(100, (int) $fresh->stamina);
        $this->assertSame(50, (int) $fresh->shot);
    }
}

<?php

namespace Tests\Feature;

use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Services\MoraleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Mise à jour hebdomadaire du moral (MoraleService::applyAfterWeek) :
 * résultat sportif, temps de jeu, bonus capitaine, et journalisation. Salaire
 * calé sur la valeur du joueur pour isoler les effets testés.
 */
class MoraleServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private MoraleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MoraleService::class);
    }

    public function test_win_and_playing_time_raise_morale_loss_lowers_it(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $home = $this->makeTeam($save);
        $away = $this->makeTeam($save);

        // salary == cost → ratio 1.0, aucun effet salaire ; moral neutre → decay nul.
        $homePlayer = $this->makePlayer($save, ['morale' => 60, 'cost' => 1000]);
        $awayPlayer = $this->makePlayer($save, ['morale' => 60, 'cost' => 1000]);
        $this->makeContract($save, $home, $homePlayer, ['salary' => 1000, 'is_starter' => true]);
        $this->makeContract($save, $away, $awayPlayer, ['salary' => 1000, 'is_starter' => true]);

        $this->makeMatch($save, $home, $away, ['week' => 1, 'status' => 'played', 'home_score' => 2, 'away_score' => 0]);

        $this->service->applyAfterWeek($save, 1);

        // Vainqueur titulaire : victoire (+2) + a joué (+1) = +3.
        $this->assertSame(63, (int) $homePlayer->fresh()->morale);
        // Perdant titulaire : défaite (−2) + a joué (+1) = −1.
        $this->assertSame(59, (int) $awayPlayer->fresh()->morale);
    }

    public function test_captain_is_more_affected_by_the_result(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $home = $this->makeTeam($save);
        $away = $this->makeTeam($save);

        $captain = $this->makePlayer($save, ['morale' => 60, 'cost' => 1000]);
        $this->makeContract($save, $home, $captain, ['salary' => 1000, 'is_starter' => true, 'is_captain' => true]);

        $awayPlayer = $this->makePlayer($save, ['morale' => 60, 'cost' => 1000]);
        $this->makeContract($save, $away, $awayPlayer, ['salary' => 1000, 'is_starter' => true]);

        $this->makeMatch($save, $home, $away, ['week' => 1, 'status' => 'played', 'home_score' => 1, 'away_score' => 0]);

        $this->service->applyAfterWeek($save, 1);

        // Capitaine : victoire 2 × 1.5 = 3, + a joué (+1) = +4.
        $this->assertSame(64, (int) $captain->fresh()->morale);
    }

    public function test_morale_changes_are_logged(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $home = $this->makeTeam($save);
        $away = $this->makeTeam($save);

        $player = $this->makePlayer($save, ['morale' => 60, 'cost' => 1000]);
        $this->makeContract($save, $home, $player, ['salary' => 1000, 'is_starter' => true]);
        $awayPlayer = $this->makePlayer($save, ['morale' => 60, 'cost' => 1000]);
        $this->makeContract($save, $away, $awayPlayer, ['salary' => 1000, 'is_starter' => true]);

        $this->makeMatch($save, $home, $away, ['week' => 1, 'status' => 'played', 'home_score' => 3, 'away_score' => 1]);

        $this->service->applyAfterWeek($save, 1);

        $logs = GamePlayerMoraleLog::where('game_player_id', $player->id)->get();
        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->contains('source', 'result'));
        $this->assertTrue($logs->contains('source', 'playing_time'));
    }

    public function test_no_played_match_leaves_morale_untouched(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $home = $this->makeTeam($save);
        $away = $this->makeTeam($save);
        $player = $this->makePlayer($save, ['morale' => 55, 'cost' => 1000]);
        $this->makeContract($save, $home, $player, ['salary' => 1000]);

        // Match seulement programmé, pas joué.
        $this->makeMatch($save, $home, $away, ['week' => 1, 'status' => 'scheduled']);

        $this->service->applyAfterWeek($save, 1);

        $this->assertSame(55, (int) $player->fresh()->morale, 'Sans match joué, rien ne change.');
    }
}

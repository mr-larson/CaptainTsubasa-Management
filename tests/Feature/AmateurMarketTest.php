<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Services\Ai\AITransferService;
use App\Services\Transfers\AmateurMarketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Marché : un contingent d'amateurs bon marché reste toujours disponible,
 * et l'IA ne les engage qu'en cas d'urgence (le marché ne se vide plus).
 */
class AmateurMarketTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_floor_creates_weak_and_cheap_free_players_per_position(): void
    {
        $save = $this->makeSave();

        $created = app(AmateurMarketService::class)->ensureFloor($save);

        $amateurs = GamePlayer::where('game_save_id', $save->id)->where('origin', GamePlayer::ORIGIN_AMATEUR)->get();
        $this->assertSame(array_sum(AmateurMarketService::FLOOR_BY_POSITION), $created);
        $this->assertCount($created, $amateurs);
        foreach (AmateurMarketService::FLOOR_BY_POSITION as $position => $floor) {
            $this->assertSame($floor, $amateurs->where('position', $position)->count());
        }

        // Nettement sous la base (stats 15+, salaire 22+).
        $this->assertTrue($amateurs->every(fn ($p) => $p->cost <= 15 && $p->shot <= 13 && $p->pass <= 13 && $p->tackle <= 13));

        // Idempotent tant que personne n'est engagé.
        $this->assertSame(0, app(AmateurMarketService::class)->ensureFloor($save));
    }

    public function test_floor_is_refilled_after_a_signing(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save);
        app(AmateurMarketService::class)->ensureFloor($save);

        $gk = GamePlayer::where('game_save_id', $save->id)->where('position', 'Goalkeeper')->first();
        $this->makeContract($save, $team, $gk);

        $this->assertSame(1, app(AmateurMarketService::class)->ensureFloor($save));
    }

    public function test_ai_leaves_amateurs_to_the_player_unless_in_emergency(): void
    {
        $save = $this->makeSave();
        [$ai] = $this->makeTeamWithSquad($save, ['budget' => 100000], 12); // 12 joueurs : pas d'urgence
        $this->makeTeam($save);
        app(AmateurMarketService::class)->ensureFloor($save);

        app(AITransferService::class)->recruitForWeek($save);

        $signedAmateurs = GameContract::where('game_save_id', $save->id)
            ->where('game_team_id', $ai->id)
            ->whereHas('gamePlayer', fn ($q) => $q->where('origin', GamePlayer::ORIGIN_AMATEUR))
            ->count();
        $this->assertSame(0, $signedAmateurs);

        // Effectif vide (urgence) : les amateurs deviennent une option.
        $empty = $this->makeTeam($save, ['budget' => 100000]);
        app(AITransferService::class)->recruitForWeek($save);
        $this->assertGreaterThan(0, GameContract::where('game_team_id', $empty->id)->count());
    }
}

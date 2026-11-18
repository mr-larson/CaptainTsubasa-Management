<?php

namespace Tests\Feature;

use App\Contracts\Randomizer;
use App\Enums\Nationality;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Services\Ai\AITransferService;
use App\Services\Transfers\AmateurMarketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('amateurNationalities')]
    public function test_generated_identity_matches_the_chosen_nationality(string $nationality, string $firstname, string $lastname): void
    {
        $save = $this->makeSave();
        $random = \Mockery::mock(Randomizer::class);
        $random->shouldReceive('pick')->once()
            ->withArgs(fn (array $items) => in_array($nationality, $items, true))
            ->andReturn($nationality);
        $random->shouldReceive('pick')->once()
            ->withArgs(fn (array $items) => in_array($firstname, $items, true))
            ->andReturn($firstname);
        $random->shouldReceive('pick')->once()
            ->withArgs(fn (array $items) => in_array($lastname, $items, true))
            ->andReturn($lastname);
        $random->shouldReceive('int')->andReturnUsing(fn (int $min, int $max) => $min);

        $player = (new AmateurMarketService($random))->generate($save, 'Midfielder')->fresh();

        $this->assertSame($nationality, $player->nationality);
        $this->assertSame($firstname, $player->firstname);
        $this->assertSame($lastname, $player->lastname);
        $this->assertSame(GamePlayer::ORIGIN_AMATEUR, $player->origin);
        $this->assertNull($player->photo_path);
        $this->assertLessThanOrEqual(15, $player->cost);
    }

    public static function amateurNationalities(): array
    {
        return [
            'japon' => [Nationality::JAPON, 'Akio', 'Abe'],
            'belgique' => [Nationality::BELGIQUE, 'Arnaud', 'Lambert'],
            'france' => [Nationality::FRANCE, 'Benoît', 'Lefèvre'],
            'bresil' => [Nationality::BRESIL, 'Vinícius', 'Almeida'],
            'maroc' => [Nationality::MAROC, 'Yassine', 'Idrissi'],
            'coree' => [Nationality::COREE_DU_SUD, 'Min-jun', 'Han'],
            'arabie' => [Nationality::ARABIE_SAOUDITE, 'Omar', 'Al-Harbi'],
        ];
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

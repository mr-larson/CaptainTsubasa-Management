<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameSanction;
use App\Services\Matches\MatchSimulator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Les matchs simulés (IA contre IA) produisent fautes, cartons et blessures
 * avec les mêmes règles que le moteur client : sans ça, seule l'équipe du
 * joueur humain subissait blessures et suspensions sur la saison.
 */
class SimulatedMatchFoulsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_simulated_matches_generate_cards_and_injuries(): void
    {
        $save = $this->makeSave();
        [$home] = $this->makeTeamWithSquad($save, [], 14);
        [$away] = $this->makeTeamWithSquad($save, [], 14);

        // 30 matchs sur des semaines distinctes : ~1,5 faute grave par match attendue.
        $matches = collect(range(1, 30))->map(fn ($week) => $this->makeMatch($save, $home, $away, ['week' => $week]));

        app(MatchSimulator::class)->simulateMatchesCollection($matches, $save);

        $sanctions = GameSanction::where('game_save_id', $save->id)->count();
        $injuries  = GameInjury::where('game_save_id', $save->id)->count();

        $this->assertGreaterThan(0, $sanctions, 'Aucun carton sur 30 matchs simulés.');
        $this->assertGreaterThan(0, $injuries, 'Aucune blessure sur 30 matchs simulés.');

        // Les fautes ne polluent pas le match_stats stocké.
        $this->assertArrayNotHasKey('foul_events', $matches->first()->fresh()->match_stats);
    }
}

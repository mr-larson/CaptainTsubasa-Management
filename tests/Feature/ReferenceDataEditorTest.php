<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Écrans joueurs / équipes de la base : liste + fiche sur une seule page,
 * création sur place (fiche vide) et contrat édité depuis la fiche joueur.
 */
class ReferenceDataEditorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->is_admin = true; // hors $fillable
        $user->save();

        return $user;
    }

    public function test_legacy_create_urls_open_the_list_in_creation_mode(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('players.create'))
            ->assertRedirect(route('players.index', ['create' => 1]));
        $this->actingAs($admin)->get(route('teams.create'))
            ->assertRedirect(route('teams.index', ['create' => 1]));

        $this->actingAs($admin)->get(route('players.index', ['create' => 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Players/Index')->where('startCreate', true));
    }

    public function test_store_selects_the_new_player_and_team(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('players.store'), [
            'firstname' => 'Kojiro',
            'lastname'  => 'Hyuga',
            'age'       => 15,
            'position'  => 'Forward',
            'cost'      => 80,
            'stats'     => ['speed' => 60, 'shot' => 70],
        ])->assertSessionHasNoErrors();
        $player = Player::where('lastname', 'Hyuga')->firstOrFail();

        $this->actingAs($admin)->post(route('teams.store'), [
            'name'   => 'Toho',
            'budget' => 1000,
        ])->assertSessionHasNoErrors();
        $team = Team::where('name', 'Toho')->firstOrFail();

        $this->assertNotNull($player->id);
        $this->assertNotNull($team->id);

        $this->actingAs($admin)->get(route('players.index', ['selected' => $player->id]))
            ->assertInertia(fn ($page) => $page->where('selectedId', $player->id)->has('teams'));
    }

    public function test_contract_saved_from_player_screen_returns_to_it(): void
    {
        $admin = $this->admin();
        $team = Team::create(['name' => 'Nankatsu', 'budget' => 1000]);
        $player = Player::create(['firstname' => 'Tsubasa', 'lastname' => 'Ozora', 'age' => 12, 'position' => 'Midfielder', 'cost' => 50, 'stats' => ['speed' => 60]]);
        $from = route('players.index', ['selected' => $player->id]);

        $this->actingAs($admin)->from($from)->post(route('contracts.store'), [
            'player_id'     => $player->id,
            'team_id'       => $team->id,
            'salary'        => 50,
            'matches_total' => 10,
        ])->assertRedirect($from);

        $this->assertSame(1, Contract::where('player_id', $player->id)->count());
    }

    public function test_contract_created_from_contracts_screen_is_opened(): void
    {
        $admin = $this->admin();
        $team = Team::create(['name' => 'Toho', 'budget' => 1000]);
        $player = Player::create(['firstname' => 'Kojiro', 'lastname' => 'Hyuga', 'age' => 15, 'position' => 'Forward', 'cost' => 80, 'stats' => ['speed' => 60]]);

        $this->actingAs($admin)->get(route('contracts.create'))
            ->assertRedirect(route('contracts.index', ['create' => 1]));

        $response = $this->actingAs($admin)->post(route('contracts.store'), [
            'player_id'      => $player->id,
            'team_id'        => $team->id,
            'salary'         => 80,
            'matches_total'  => 12,
            'matches_played' => 5, // ignoré : un contrat neuf démarre à 0
            'open_created'   => true,
        ]);

        $contract = Contract::where('player_id', $player->id)->firstOrFail();
        $response->assertRedirect(route('contracts.index', ['selected' => $contract->id]));
        $this->assertSame(0, (int) $contract->matches_played);

        $this->actingAs($admin)->get(route('contracts.index', ['selected' => $contract->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Contracts/Index')
                ->where('selectedId', $contract->id)
                ->where('contracts.0.team_name', 'Toho'));
    }
}

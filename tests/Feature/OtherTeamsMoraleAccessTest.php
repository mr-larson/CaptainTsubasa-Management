<?php

namespace Tests\Feature;

use App\Models\GameSaves\GamePromise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Option « Accès aux autres clubs » : promesses et déclarations publiques sur
 * les joueurs des équipes IA, et carte « Moral » chargée à la demande.
 */
class OtherTeamsMoraleAccessTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function world(bool $editable): array
    {
        $save = $this->makeSave();
        [$mine]                 = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        [$rival, $rivalPlayers] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 2]);
        [$ai, $aiPlayers]       = $this->makeTeamWithSquad($save);
        $state = $save->state ?? [];
        $state['config'] = array_merge($state['config'] ?? [], ['other_teams_editable' => $editable]);
        $save->update(['controlled_game_team_id' => $mine->id, 'state' => $state]);

        return [$save->fresh(), User::find($save->user_id), $aiPlayers[0], $rivalPlayers[0]];
    }

    public function test_promise_and_declaration_on_an_ai_player_are_refused_without_the_option(): void
    {
        [$save, $user, $aiPlayer] = $this->world(false);

        $this->actingAs($user)
            ->post(route('game-saves.players.promises.store', [$save, $aiPlayer]), ['type' => 'playing_time'])
            ->assertSessionHasErrors('promise');

        $this->actingAs($user)
            ->post(route('game-saves.players.declarations.store', [$save, $aiPlayer]), ['type' => 'praise'])
            ->assertSessionHasErrors('declaration');
    }

    public function test_promise_and_declaration_on_an_ai_player_are_allowed_with_the_option(): void
    {
        [$save, $user, $aiPlayer, $rivalPlayer] = $this->world(true);

        $this->actingAs($user)
            ->post(route('game-saves.players.promises.store', [$save, $aiPlayer]), ['type' => 'playing_time'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, GamePromise::where('game_player_id', $aiPlayer->id)->count());

        $this->actingAs($user)
            ->post(route('game-saves.players.declarations.store', [$save, $aiPlayer]), ['type' => 'praise'])
            ->assertSessionHasNoErrors();

        // L'autre manager humain reste protégé.
        $this->actingAs($user)
            ->post(route('game-saves.players.promises.store', [$save, $rivalPlayer]), ['type' => 'playing_time'])
            ->assertSessionHasErrors('promise');

        // Carte « Moral » à la demande.
        $this->actingAs($user)
            ->getJson(route('game-saves.players.morale', [$save, $aiPlayer]))
            ->assertOk()
            ->assertJsonCount(1, 'promises')
            ->assertJsonCount(1, 'declarations')
            ->assertJsonStructure(['logs', 'promises', 'declarations']);
    }
}

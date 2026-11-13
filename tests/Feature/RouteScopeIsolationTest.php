<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Une ressource d'une AUTRE sauvegarde du même utilisateur, passée sous l'URL de
 * la sauvegarde courante, est refusée (404) par le middleware save.scope, sans
 * que chaque contrôleur ait à y penser.
 */
class RouteScopeIsolationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_children_of_another_save_are_not_reachable_through_this_save(): void
    {
        $mine  = $this->makeSave();
        $other = $this->makeSave(['user_id' => $mine->user_id]);
        $user  = User::find($mine->user_id);

        [$otherTeam, $otherPlayers] = $this->makeTeamWithSquad($other);
        $otherContract = $otherPlayers[0]->contracts()->first();
        $otherMatch    = $this->makeMatch($other, $otherTeam, $this->makeTeam($other));

        $this->actingAs($user);

        $this->patch(route('game-saves.contracts.toggle-starter', [$mine, $otherContract]))->assertNotFound();
        $this->patch(route('game-saves.contracts.toggle-captain', [$mine, $otherContract]))->assertNotFound();
        $this->get(route('game-saves.players.edit', [$mine, $otherPlayers[0]]))->assertNotFound();
        $this->post(route('game-saves.teams.update', [$mine, $otherTeam]), ['name' => 'x'])->assertNotFound();
        $this->get(route('game-saves.matches.stats', [$mine, $otherMatch]))->assertNotFound();

        // Même ressource sous sa vraie sauvegarde : pas bloquée par le contrôle.
        $this->patch(route('game-saves.contracts.toggle-captain', [$other, $otherContract]))->assertStatus(302);
    }
}

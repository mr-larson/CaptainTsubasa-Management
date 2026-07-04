<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSave;
use App\Models\Player;
use App\Models\PlayerLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Duos d'alchimie (player_links) : résolution des partenaires et exposition
 * dans les props du match (duo_partners), qui débloque l'action une-deux
 * côté moteur quand les deux joueurs liés sont sur le terrain.
 */
class PlayerLinkTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function makeBasePlayer(string $firstname, string $lastname): Player
    {
        return Player::create([
            'firstname' => $firstname,
            'lastname'  => $lastname,
            'age'       => 18,
            'position'  => 'Midfielder',
            'cost'      => 100,
            'stats'     => ['speed' => 50, 'stamina' => 50, 'attack' => 50, 'defense' => 50],
        ]);
    }

    public function test_partner_map_is_symmetric_and_scoped_to_the_given_set(): void
    {
        $tsubasa = $this->makeBasePlayer('Tsubasa', 'Ozora');
        $misaki  = $this->makeBasePlayer('Taro', 'Misaki');
        $hyuga   = $this->makeBasePlayer('Kojiro', 'Hyuga');

        PlayerLink::create([
            'player_a_id' => $tsubasa->id,
            'player_b_id' => $misaki->id,
            'label'       => 'Golden Combi',
        ]);

        // Les deux membres dans l'ensemble → lien symétrique
        $map = PlayerLink::partnerMapFor([$tsubasa->id, $misaki->id, $hyuga->id]);
        $this->assertSame($misaki->id, $map[$tsubasa->id][0]['partner_id']);
        $this->assertSame($tsubasa->id, $map[$misaki->id][0]['partner_id']);
        $this->assertSame('Golden Combi', $map[$tsubasa->id][0]['label']);
        $this->assertArrayNotHasKey($hyuga->id, $map);

        // Partenaire hors ensemble → filtré par défaut (cas match : même équipe)
        $this->assertSame([], PlayerLink::partnerMapFor([$tsubasa->id]));

        // withinSetOnly=false → le lien sort même si le partenaire est ailleurs
        $open = PlayerLink::partnerMapFor([$tsubasa->id], withinSetOnly: false);
        $this->assertSame($misaki->id, $open[$tsubasa->id][0]['partner_id']);
    }

    public function test_links_with_names_resolves_partner_names_from_catalog(): void
    {
        $tsubasa = $this->makeBasePlayer('Tsubasa', 'Ozora');
        $misaki  = $this->makeBasePlayer('Taro', 'Misaki');

        PlayerLink::create([
            'player_a_id' => $tsubasa->id,
            'player_b_id' => $misaki->id,
            'label'       => 'Golden Combi',
        ]);

        $links = PlayerLink::linksWithNamesFor([$tsubasa->id]);

        $this->assertSame('Misaki', $links[$tsubasa->id][0]['partner_name']);
        $this->assertSame('Golden Combi', $links[$tsubasa->id][0]['label']);
        $this->assertSame($misaki->id, $links[$tsubasa->id][0]['partner_base_id']);
    }

    public function test_match_props_expose_duo_partners_within_the_same_team(): void
    {
        $user = User::factory()->create();
        $save = $this->makeSave(['user_id' => $user->id]);

        [$human, $humanPlayers] = $this->makeTeamWithSquad($save, ['is_controlled' => true]);
        [$ai] = $this->makeTeamWithSquad($save);

        $save->controlled_game_team_id = $human->id;
        $save->save();

        $this->makeMatch($save, $human, $ai);

        // Duo au catalogue, reporté sur deux titulaires de l'équipe humaine
        $tsubasa = $this->makeBasePlayer('Tsubasa', 'Ozora');
        $misaki  = $this->makeBasePlayer('Taro', 'Misaki');
        PlayerLink::create([
            'player_a_id' => $tsubasa->id,
            'player_b_id' => $misaki->id,
            'label'       => 'Golden Combi',
        ]);
        $humanPlayers[5]->update(['base_player_id' => $tsubasa->id]);
        $humanPlayers[6]->update(['base_player_id' => $misaki->id]);

        $response = $this->actingAs($user)->get(route('game-saves.match', $save));
        $response->assertOk();

        $players = collect($response->viewData('page')['props']['engineConfig']['teams']['internal']['players']);

        $carrier = $players->firstWhere('id', $humanPlayers[5]->id);
        $partner = $players->firstWhere('id', $humanPlayers[6]->id);
        $other   = $players->firstWhere('id', $humanPlayers[0]->id);

        $this->assertSame($humanPlayers[6]->id, $carrier['duo_partners'][0]['id']);
        $this->assertSame('Golden Combi', $carrier['duo_partners'][0]['label']);
        $this->assertSame($humanPlayers[5]->id, $partner['duo_partners'][0]['id']);
        $this->assertSame([], $other['duo_partners']);
    }
}

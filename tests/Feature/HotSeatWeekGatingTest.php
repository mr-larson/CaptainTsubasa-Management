<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotSeatWeekGatingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Avec deux équipes humaines, jouer le match du premier joueur ne doit
     * PAS clore la semaine : le match de l'autre humain reste à jouer et la
     * semaine n'avance qu'au dernier match humain.
     */
    public function test_week_advances_only_after_all_human_matches_played(): void
    {
        $user = User::factory()->create();

        $save = GameSave::create([
            'user_id'   => $user->id,
            'team_id'   => null,
            'period'    => 'college',
            'season'    => 1,
            'week'      => 1,
            'phase'     => 'season',
            'game_mode' => 'prebuilt',
            'state'     => null,
        ]);

        // 2 équipes humaines (sièges 1 & 2) + 2 équipes IA → seasonLength = 6.
        $human1 = $this->gameTeam($save, 'Humain 1', true, 1);
        $human2 = $this->gameTeam($save, 'Humain 2', true, 2);
        $aiX    = $this->gameTeam($save, 'IA X', false, null);
        $aiY    = $this->gameTeam($save, 'IA Y', false, null);

        $save->controlled_game_team_id = $human1->id;
        $save->save();

        // Semaine 1 : chaque humain joue contre une IA.
        $matchA = $this->match($save, 1, $human1, $aiX);
        $matchB = $this->match($save, 1, $human2, $aiY);

        // ── Le joueur 1 termine son match : la semaine NE doit PAS avancer ──
        $this->actingAs($user)
            ->post(route('game-saves.matches.finish', [$save, $matchA]), [
                'scoresByTeamId' => [$human1->id => 2, $aiX->id => 1],
                'playerActions'  => [],
                'foulEvents'     => [],
            ])
            ->assertRedirect();

        $this->assertSame('played',    $matchA->fresh()->status);
        $this->assertSame('scheduled', $matchB->fresh()->status, 'Le match du 2e humain ne doit pas être simulé.');
        $this->assertSame(1, (int) $save->fresh()->week, 'La semaine ne doit pas avancer tant qu\'un humain n\'a pas joué.');

        // Handoff : la main passe au joueur 2 (siège suivant non joué).
        $this->assertSame($human2->id, (int) $save->fresh()->controlled_game_team_id, 'La main doit passer au joueur 2.');

        // ── Le joueur 2 termine son match : la semaine se clôt ──
        $this->actingAs($user)
            ->post(route('game-saves.matches.finish', [$save, $matchB]), [
                'scoresByTeamId' => [$human2->id => 0, $aiY->id => 0],
                'playerActions'  => [],
                'foulEvents'     => [],
            ])
            ->assertRedirect();

        $this->assertSame('played', $matchB->fresh()->status);
        $this->assertSame(2, (int) $save->fresh()->week, 'La semaine doit avancer une fois tous les matchs humains joués.');

        // Nouvelle semaine : la main revient au joueur 1 (siège 1).
        $this->assertSame($human1->id, (int) $save->fresh()->controlled_game_team_id, 'La main doit revenir au joueur 1 pour la nouvelle semaine.');
    }

    /**
     * En mono-joueur, jouer son unique match clôt la semaine immédiatement
     * (comportement historique inchangé).
     */
    public function test_single_human_match_closes_week_immediately(): void
    {
        $user = User::factory()->create();

        $save = GameSave::create([
            'user_id'   => $user->id,
            'team_id'   => null,
            'period'    => 'college',
            'season'    => 1,
            'week'      => 1,
            'phase'     => 'season',
            'game_mode' => 'prebuilt',
            'state'     => null,
        ]);

        $human = $this->gameTeam($save, 'Humain', true, 1);
        $aiX   = $this->gameTeam($save, 'IA X', false, null);
        $this->gameTeam($save, 'IA Y', false, null);
        $this->gameTeam($save, 'IA Z', false, null);

        $save->controlled_game_team_id = $human->id;
        $save->save();

        $match = $this->match($save, 1, $human, $aiX);

        $this->actingAs($user)
            ->post(route('game-saves.matches.finish', [$save, $match]), [
                'scoresByTeamId' => [$human->id => 1, $aiX->id => 0],
                'playerActions'  => [],
                'foulEvents'     => [],
            ])
            ->assertRedirect();

        $this->assertSame(2, (int) $save->fresh()->week);
    }

    private function gameTeam(GameSave $save, string $name, bool $controlled, ?int $seat): GameTeam
    {
        return GameTeam::create([
            'game_save_id'  => $save->id,
            'base_team_id'  => null,
            'is_controlled' => $controlled,
            'human_seat'    => $seat,
            'name'          => $name,
            'budget'        => 100000,
            'wins'          => 0,
            'draws'         => 0,
            'losses'        => 0,
        ]);
    }

    private function match(GameSave $save, int $week, GameTeam $home, GameTeam $away): GameMatch
    {
        return GameMatch::create([
            'game_save_id' => $save->id,
            'week'         => $week,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'status'       => 'scheduled',
        ]);
    }
}

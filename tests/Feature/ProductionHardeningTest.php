<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSave;
use App\Models\Team;
use App\Models\User;
use App\Services\AITrainingService;
use App\Services\PostMatchProgressionService;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Durcissement prod (lot 4 de la revue backend) : taille du state bornée,
 * clés serveur non envoyées au front, seeders de référence protégés en prod,
 * logos uploadés sur le disque servi par le front.
 */
class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    /** PNG 1×1 valide. */
    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    public function test_finish_match_no_longer_stores_client_player_actions(): void
    {
        $save  = $this->makeSave();
        $human = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $ai    = $this->makeTeam($save);
        $save->update(['controlled_game_team_id' => $human->id]);
        $match = $this->makeMatch($save, $human, $ai);

        $this->actingAs(User::find($save->user_id))
            ->post(route('game-saves.matches.finish', [$save, $match]), [
                'scoresByTeamId' => [$human->id => 1, $ai->id => 0],
                'playerActions'  => array_fill(0, 50, ['attack' => ['game_player_id' => 1, 'action' => 'shot']]),
            ])
            ->assertRedirect();

        $this->assertArrayNotHasKey('player_actions', $save->fresh()->state ?? []);
    }

    public function test_ai_training_history_keeps_only_the_current_season(): void
    {
        $save = $this->makeSave(['season' => 2, 'week' => 3, 'state' => [
            'ai_training_history' => [
                ['season' => 1, 'week' => 9, 'team_id' => 1],
                ['season' => 2, 'week' => 2, 'team_id' => 1],
            ],
        ]]);
        $this->makeTeamWithSquad($save);

        app(AITrainingService::class)->trainForWeek($save);

        $seasons = array_unique(array_column($save->fresh()->state['ai_training_history'], 'season'));
        $this->assertSame([2], array_values(array_map('intval', $seasons)));
    }

    public function test_season_progression_keeps_only_the_current_season(): void
    {
        $save = $this->makeSave(['season' => 2, 'state' => ['season_progression' => [1 => [5 => 40]]]]);
        [$team] = $this->makeTeamWithSquad($save);
        $scorer = $this->makePlayer($save, ['position' => 'ATT']);
        $this->makeContract($save, $team, $scorer);
        $match = $this->makeMatch($save, $team, $team, [
            'status'      => 'played',
            'match_stats' => ['players' => [(string) $scorer->id => ['offense' => ['goals' => 2], 'defense' => [], 'duelsWon' => 0]]],
        ]);

        app(PostMatchProgressionService::class)->applyForMatch($save, $match);

        $this->assertSame([2], array_keys($save->fresh()->state['season_progression']));
    }

    public function test_server_only_state_keys_are_not_serialized(): void
    {
        $save = $this->makeSave(['state' => [
            'shop'     => ['offers_by_team' => ['1' => []]],
            'lineup'   => ['1' => ['slots' => []]],
            'training' => ['season' => 1, 'week' => 1],
        ]]);

        $state = $save->toArray()['state'];

        $this->assertArrayNotHasKey('shop', $state);
        $this->assertArrayHasKey('lineup', $state);
        $this->assertArrayHasKey('training', $state);
        $this->assertArrayHasKey('shop', $save->state, 'Le state en base reste complet.');
    }

    public function test_reference_seeder_refuses_to_truncate_a_populated_table_in_production(): void
    {
        $team = Team::create(['name' => 'Nankatsu', 'budget' => 1000]);
        $this->app['env'] = 'production';

        // Appel direct : `db:seed` demanderait une confirmation interactive en prod.
        app(TeamSeeder::class)->run();

        $this->assertSame(1, Team::count());
        $this->assertSame('Nankatsu', Team::first()->name);
        $this->assertSame($team->id, Team::first()->id);
    }

    public function test_game_team_logo_is_stored_on_the_public_disk_served_under_storage(): void
    {
        Storage::fake('public');
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['name' => 'Toho']);

        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, base64_decode(self::PNG_1PX));

        $this->actingAs(User::find($save->user_id))
            ->post(route('game-saves.teams.update', [$save, $team]), [
                'name'   => 'Toho',
                'budget' => 1000,
                'logo'   => new UploadedFile($path, 'toho.png', null, null, true),
            ])
            ->assertSessionHasNoErrors();

        $logo = $team->fresh()->logo_path;
        $this->assertStringStartsWith("images/game-teams/{$save->id}/", $logo);
        Storage::disk('public')->assertExists($logo);
    }

    public function test_stale_logo_path_sent_by_the_form_does_not_override_the_new_logo(): void
    {
        Storage::fake('public');
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['name' => 'Toho', 'logo_path' => "images/game-teams/{$save->id}/old.png"]);

        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, base64_decode(self::PNG_1PX));

        // Le formulaire renvoie l'ancien chemin en plus du nouveau fichier.
        $this->actingAs(User::find($save->user_id))
            ->post(route('game-saves.teams.update', [$save, $team]), [
                'name'      => 'Toho',
                'budget'    => 1000,
                'logo_path' => "images/game-teams/{$save->id}/old.png",
                'logo'      => new UploadedFile($path, 'toho.png', null, null, true),
            ])
            ->assertSessionHasNoErrors();

        $logo = $team->fresh()->logo_path;
        $this->assertNotSame("images/game-teams/{$save->id}/old.png", $logo);
        Storage::disk('public')->assertExists($logo);
    }
}

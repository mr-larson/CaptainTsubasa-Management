<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\User;
use App\Services\Matches\FoulAndInjuryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Régressions sécurité / intégrité (lot 3 de la revue backend) : contrats
 * scopés à la save, uploads, suppressions en cascade, mise à jour de save
 * limitée au libellé, événements de faute limités aux joueurs du match.
 */
class SecurityIntegrityTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    /** PNG 1×1 valide. */
    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private function realUpload(string $bytes, string $clientName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, $bytes);

        return new UploadedFile($path, $clientName, null, null, true);
    }

    private function owner(GameSave $save): User
    {
        return User::find($save->user_id);
    }

    public function test_contract_cannot_target_a_team_of_another_save(): void
    {
        $save    = $this->makeSave();
        $player  = $this->makePlayer($save);
        $foreign = $this->makeTeam($this->makeSave()); // save d'un autre utilisateur

        $this->actingAs($this->owner($save))
            ->post(route('game-saves.contracts.store', [$save, $player]), [
                'game_team_id' => $foreign->id,
                'salary'       => 100,
                'start_week'   => 1,
            ])
            ->assertSessionHasErrors('game_team_id');

        $this->assertSame(0, GameContract::count());
    }

    public function test_contract_end_week_cannot_precede_start_week(): void
    {
        $save   = $this->makeSave();
        $team   = $this->makeTeam($save);
        $player = $this->makePlayer($save);

        $this->actingAs($this->owner($save))
            ->post(route('game-saves.contracts.store', [$save, $player]), [
                'game_team_id' => $team->id,
                'salary'       => 100,
                'start_week'   => 5,
                'end_week'     => 2,
            ])
            ->assertSessionHasErrors('end_week');
    }

    public function test_uploaded_photo_extension_comes_from_content_not_client_name(): void
    {
        Storage::fake('public');
        $save = $this->makeSave();

        $this->actingAs($this->owner($save))
            ->post(route('game-saves.players.store', $save), [
                'firstname' => 'Taro',
                'lastname'  => 'Misaki',
                'position'  => 'Midfielder',
                'cost'      => 100,
                // Vrai fichier (le MIME est sniffé sur le contenu, comme en prod ;
                // les fakes de Laravel le déduisent du nom).
                'photo'     => $this->realUpload(base64_decode(self::PNG_1PX), 'evil.html'),
            ])
            ->assertSessionHasNoErrors();

        $path = GamePlayer::where('lastname', 'Misaki')->value('photo_path');
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_svg_photo_is_rejected(): void
    {
        Storage::fake('public');
        $save = $this->makeSave();

        $this->actingAs($this->owner($save))
            ->post(route('game-saves.players.store', $save), [
                'firstname' => 'Taro',
                'lastname'  => 'Misaki',
                'position'  => 'Midfielder',
                'cost'      => 100,
                'photo'     => UploadedFile::fake()->createWithContent(
                    'logo.svg',
                    '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
                ),
            ])
            ->assertSessionHasErrors('photo');
    }

    public function test_deleting_a_team_with_contracts_cascades_instead_of_failing(): void
    {
        $save = $this->makeSave();
        [$team, $players] = $this->makeTeamWithSquad($save);

        $this->actingAs($this->owner($save))
            ->delete(route('game-saves.teams.destroy', [$save, $team]))
            ->assertRedirect();

        $this->assertNull($team->fresh());
        $this->assertSame(0, GameContract::where('game_team_id', $team->id)->count());
        $this->assertNotNull($players->first()->fresh(), 'Les joueurs redeviennent agents libres.');
    }

    public function test_deleting_a_player_with_an_expired_contract_succeeds(): void
    {
        $save   = $this->makeSave(['week' => 10]);
        $team   = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $this->makeContract($save, $team, $player, ['start_week' => 1, 'end_week' => 4]);

        $this->actingAs($this->owner($save))
            ->delete(route('game-saves.players.destroy', [$save, $player]))
            ->assertRedirect();

        $this->assertNull($player->fresh());
    }

    public function test_save_update_only_changes_the_label(): void
    {
        $save = $this->makeSave(['week' => 4, 'season' => 1, 'state' => ['config' => ['bonus_cards_enabled' => true]]]);

        $this->actingAs($this->owner($save))
            ->put(route('game-saves.update', $save), [
                'label'  => 'Ma partie',
                'week'   => 30,
                'season' => 9,
                'state'  => ['hacked' => true],
            ])
            ->assertRedirect();

        $fresh = $save->fresh();
        $this->assertSame('Ma partie', $fresh->label);
        $this->assertSame(4, (int) $fresh->week);
        $this->assertSame(1, (int) $fresh->season);
        $this->assertSame(['config' => ['bonus_cards_enabled' => true]], $fresh->state);
    }

    public function test_foul_events_ignore_players_outside_the_match(): void
    {
        $save = $this->makeSave();
        [$home, $homePlayers] = $this->makeTeamWithSquad($save);
        [$away] = $this->makeTeamWithSquad($save);
        [, $bystanders] = $this->makeTeamWithSquad($save); // prochain adversaire, hors match
        $match = $this->makeMatch($save, $home, $away, ['status' => 'played']);

        app(FoulAndInjuryService::class)->processMatchEvents($save, $match, [
            ['type' => 'injury', 'player_id' => $bystanders->first()->id, 'severity' => 'severe'],
            ['type' => 'card', 'player_id' => $bystanders->last()->id, 'card_type' => 'red'],
            ['type' => 'injury', 'player_id' => $homePlayers->first()->id, 'severity' => 'light'],
        ]);

        $this->assertSame([$homePlayers->first()->id], GameInjury::pluck('game_player_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(0, \App\Models\GameSaves\GameSanction::count());
    }
}

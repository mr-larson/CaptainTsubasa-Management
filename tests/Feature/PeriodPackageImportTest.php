<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\PeriodPackage;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Services\PeriodPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use ZipArchive;

class PeriodPackageImportTest extends TestCase
{
    use RefreshDatabase;

    private function makeZipUpload(array $entries): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ppkg').'.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return new UploadedFile($path, 'package.zip', 'application/zip', null, true);
    }

    private function tinyPng(): string
    {
        $im = imagecreatetruecolor(2, 2);
        ob_start();
        imagepng($im);
        $bytes = ob_get_clean();
        imagedestroy($im);

        return $bytes;
    }

    private function validPackageEntries(array $overrides = []): array
    {
        $teams = $overrides['teams'] ?? [[
            'team_key' => 'nankatsu',
            'name' => 'Nankatsu SC',
            'budget' => 1000,
            'tactical_style' => 'balanced',
            'management_philosophy' => 'collective',
            'logo_image' => 'images/teams/nankatsu.png',
        ]];

        $players = $overrides['players'] ?? [
            [
                'player_key' => 'tsubasa',
                'firstname' => 'Tsubasa',
                'lastname' => 'Ozora',
                'position' => 'Forward',
                'nationality' => 'Japon',
                'cost' => 100,
                'attack' => 90,
                'photo_image' => 'images/players/tsubasa.png',
            ],
            [
                'player_key' => 'wakabayashi',
                'firstname' => 'Genzo',
                'lastname' => 'Wakabayashi',
                'position' => 'Goalkeeper',
                'nationality' => 'Japon',
                'cost' => 90,
                'hand_save' => 80,
            ],
        ];

        $contracts = $overrides['contracts'] ?? [
            ['team_key' => 'nankatsu', 'player_key' => 'tsubasa', 'salary' => 500, 'is_captain' => true],
            ['team_key' => 'nankatsu', 'player_key' => 'wakabayashi', 'salary' => 400],
        ];

        return [
            'manifest.json' => json_encode(['format_version' => 1, 'name' => 'Test Package']),
            'teams.json' => json_encode($teams),
            'players.json' => json_encode($players),
            'contracts.json' => json_encode($contracts),
            'images/teams/nankatsu.png' => $this->tinyPng(),
            'images/players/tsubasa.png' => $this->tinyPng(),
        ];
    }

    public function test_import_from_zip_creates_package_with_counts_and_stores_images(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $zip = $this->makeZipUpload($this->validPackageEntries());

        $package = app(PeriodPackageService::class)->importFromZip($zip, $user, [
            'name' => 'Ma période',
            'description' => 'Un effectif custom',
            'is_shared' => false,
        ]);

        $this->assertSame($user->id, $package->user_id);
        $this->assertSame(1, $package->team_count);
        $this->assertSame(2, $package->player_count);
        $this->assertFalse($package->is_shared);

        $team = collect($package->teams_json)->firstWhere('team_key', 'nankatsu');
        $this->assertNotNull($team['logo_path']);
        Storage::disk('public')->assertExists($team['logo_path']);

        $player = collect($package->players_json)->firstWhere('player_key', 'tsubasa');
        $this->assertNotNull($player['photo_path']);
        Storage::disk('public')->assertExists($player['photo_path']);

        $noPhotoPlayer = collect($package->players_json)->firstWhere('player_key', 'wakabayashi');
        $this->assertNull($noPhotoPlayer['photo_path']);
    }

    public function test_import_rejects_zip_slip_entry(): void
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->create();
        $entries = $this->validPackageEntries();
        $entries['../evil.php'] = '<?php echo "pwned"; ?>';

        app(PeriodPackageService::class)->importFromZip($this->makeZipUpload($entries), $user, ['name' => 'Bad']);
    }

    public function test_import_rejects_orphan_contract_reference(): void
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->create();
        $entries = $this->validPackageEntries([
            'contracts' => [
                ['team_key' => 'nankatsu', 'player_key' => 'does-not-exist', 'salary' => 500],
            ],
        ]);

        app(PeriodPackageService::class)->importFromZip($this->makeZipUpload($entries), $user, ['name' => 'Bad']);
    }

    public function test_import_rejects_invalid_image_content(): void
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->create();
        $entries = $this->validPackageEntries();
        $entries['images/players/tsubasa.png'] = 'this is not an image';

        app(PeriodPackageService::class)->importFromZip($this->makeZipUpload($entries), $user, ['name' => 'Bad']);
    }

    public function test_start_hydrates_game_save_from_period_package_instead_of_global_tables(): void
    {
        $user = User::factory()->create();

        $package = PeriodPackage::create([
            'user_id' => $user->id,
            'name' => 'Ma période',
            'is_shared' => false,
            'player_count' => 2,
            'team_count' => 1,
            'teams_json' => [[
                'team_key' => 'nankatsu',
                'name' => 'Nankatsu SC',
                'budget' => 1000,
                'logo_path' => null,
            ]],
            'players_json' => [
                ['player_key' => 'tsubasa', 'firstname' => 'Tsubasa', 'lastname' => 'Ozora', 'position' => 'Forward', 'attack' => 90],
                ['player_key' => 'wakabayashi', 'firstname' => 'Genzo', 'lastname' => 'Wakabayashi', 'position' => 'Goalkeeper', 'hand_save' => 80],
            ],
            'contracts_json' => [
                ['team_key' => 'nankatsu', 'player_key' => 'tsubasa', 'salary' => 500, 'is_captain' => true],
                ['team_key' => 'nankatsu', 'player_key' => 'wakabayashi', 'salary' => 400],
            ],
        ]);

        $this->actingAs($user)
            ->post(route('game-saves.start'), [
                'period' => 'college',
                'game_mode' => 'prebuilt',
                'period_package_id' => $package->id,
                'team_ids' => ['nankatsu'],
            ])
            ->assertRedirect();

        $save = GameSave::where('user_id', $user->id)->firstOrFail();
        $this->assertNull($save->team_id);

        $team = GameTeam::where('game_save_id', $save->id)->sole();
        $this->assertSame('Nankatsu SC', $team->name);
        $this->assertNull($team->base_team_id);
        $this->assertTrue($team->is_controlled);

        $players = GamePlayer::where('game_save_id', $save->id)->get();
        $this->assertCount(2, $players);
        $this->assertTrue($players->every(fn ($p) => $p->base_player_id === null));

        $tsubasa = $players->firstWhere('firstname', 'Tsubasa');
        $contract = GameContract::where('game_save_id', $save->id)
            ->where('game_player_id', $tsubasa->id)
            ->sole();
        $this->assertSame($team->id, $contract->game_team_id);
        $this->assertTrue($contract->is_captain);
        $this->assertTrue($contract->is_starter);
    }

    public function test_export_canon_template_is_reimportable(): void
    {
        $team = Team::factory()->create();
        // Mononyme : firstname vide, comme certains joueurs canon réels
        // (ex. "Alberto") — le modèle exporté doit rester réimportable.
        $mononym = Player::factory()->create(['firstname' => '', 'lastname' => 'Alberto']);
        $named   = Player::factory()->create();
        Contract::create(['team_id' => $team->id, 'player_id' => $mononym->id, 'salary' => 500, 'is_captain' => true]);
        Contract::create(['team_id' => $team->id, 'player_id' => $named->id, 'salary' => 400]);

        $service = app(PeriodPackageService::class);
        $zipPath = $service->exportCanonTemplate();

        // Le modèle ne doit pas embarquer d'images (celles-ci pèsent ~200 Mo
        // sur les vraies données canon, bien au-delà de la limite d'upload).
        $this->assertLessThan(1024 * 1024, filesize($zipPath));

        $user = User::factory()->create();
        $uploaded = new UploadedFile($zipPath, 'template.zip', 'application/zip', null, true);
        $package = $service->importFromZip($uploaded, $user, ['name' => 'Depuis le modèle']);

        $this->assertSame(1, $package->team_count);
        $this->assertSame(2, $package->player_count);
        $this->assertSame('', collect($package->players_json)->firstWhere('lastname', 'Alberto')['firstname']);

        @unlink($zipPath);
    }

    public function test_export_existing_package_round_trips(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $zip = $this->makeZipUpload($this->validPackageEntries());
        $service = app(PeriodPackageService::class);

        $package = $service->importFromZip($zip, $user, ['name' => 'Original', 'description' => 'À réexporter']);

        $exportPath = $service->exportPackageAsZip($package);
        $reimported = $service->importFromZip(
            new UploadedFile($exportPath, 'reexport.zip', 'application/zip', null, true),
            $user,
            ['name' => 'Réimporté depuis export']
        );

        $this->assertSame($package->team_count, $reimported->team_count);
        $this->assertSame($package->player_count, $reimported->player_count);

        @unlink($exportPath);
    }
}

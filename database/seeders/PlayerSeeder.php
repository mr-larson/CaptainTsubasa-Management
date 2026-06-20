<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\CalculatesWeeklyCost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlayerSeeder extends Seeder
{
    use CalculatesWeeklyCost;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // On remet la table à zéro avant de reseed
        Schema::disableForeignKeyConstraints();
        DB::table('players')->truncate();
        Schema::enableForeignKeyConstraints();

        /**
         * CONFIG IMAGES
         * - Source: mets tes PNG ici (dans ton repo)
         *   database/seeders/assets/players/firstname-lastname.png
         * - Destination: le seeder copie vers
         *   storage/app/public/players/firstname-lastname.png
         *
         * Ensuite, côté front: /storage/players/firstname-lastname.png
         */
        $imagesSourceDir = database_path('seeders/assets/players'); // <--- dossier à créer
        $storageDisk = Storage::disk('public');
        $storageDir = 'players';

        // (optionnel) crée le dossier destination si pas présent
        if (!$storageDisk->exists($storageDir)) {
            $storageDisk->makeDirectory($storageDir);
        }

        // Données joueurs regroupées par source dans database/seeders/Players/.
        // PlayerSeeder conserve toute la logique d'insertion (skills, images,
        // special moves) ; chaque groupe est tagué par son `origin` (œuvre de
        // provenance) — club et agents libres Captain Tsubasa partagent la même.
        $playersByOrigin = [
            'captain_tsubasa' => array_merge(
                Players\CaptainTsubasaClubPlayers::players(),
                Players\CaptainTsubasaFreePlayers::players(),
            ),
            'ecole_des_champions' => Players\EcoleDesChampionsPlayers::players(),
            'hungry_heart'        => Players\HungryHeartPlayers::players(),
            'blue_lock'           => Players\BlueLockPlayers::players(),
            'ao_ashi'             => Players\AoAshiPlayers::players(),
            'original'            => Players\RandomPlayers::players(),
        ];

        $specialMovesByPlayerSlug = array_merge(
            Players\CaptainTsubasaClubPlayers::specialMoves(),
            Players\CaptainTsubasaFreePlayers::specialMoves(),
            Players\EcoleDesChampionsPlayers::specialMoves(),
            Players\HungryHeartPlayers::specialMoves(),
            Players\BlueLockPlayers::specialMoves(),
            Players\AoAshiPlayers::specialMoves(),
            Players\RandomPlayers::specialMoves(),
        );

        foreach ($playersByOrigin as $origin => $players) {
        foreach ($players as $player) {
            $firstname = $player[0];
            $lastname = $player[1];
            $age = $player[2];
            $position = $player[3];
            $cost = $this->calculateWeeklyCost($player[5], $position);
            $baseStats = $player[5];
            $desc = $player[6] ?? null;
            // Postes secondaires (optionnel, index 7) : postes où le joueur peut
            // aussi évoluer, avec un bonus de poste réduit en match.
            $secondaryPositions = $player[7] ?? [];

            $fullStats = $this->buildSkills($baseStats, $position);

            // -----------------------------
            // PHOTO AUTO (si fichier existe)
            // -----------------------------
            $slug = Str::slug($firstname . ' ' . $lastname); // ex: taro-misaki

            $headingOverride = $this->getHeadingOverride($slug);
            if ($headingOverride !== null) {
                $fullStats['heading'] = $headingOverride;
            }
            $filename = $slug . '.png';
            $sourcePath = $imagesSourceDir . DIRECTORY_SEPARATOR . $filename;
            $destPath = $storageDir . '/' . $filename; // players/taro-misaki.png
            $photoPathDb = null;
            $specialMoves = $specialMovesByPlayerSlug[$slug] ?? null;
            if (is_file($sourcePath)) {
                // copie systématique : les images mises à jour dans les assets
                // écrasent celles du storage
                $storageDisk->put($destPath, file_get_contents($sourcePath));

                $photoPathDb = $destPath; // stocké en DB
            }

            DB::table('players')->insert([
                'firstname' => $firstname,
                'lastname' => $lastname,
                'age' => $age,
                'position' => $position,
                'origin' => $origin,
                'nationality' => null, // prévu pour un usage futur
                'secondary_positions' => json_encode($secondaryPositions, JSON_UNESCAPED_UNICODE),
                'cost' => $cost,
                'stats' => json_encode($fullStats, JSON_UNESCAPED_UNICODE),
                'special_moves' => $specialMoves ? json_encode($specialMoves, JSON_UNESCAPED_UNICODE)
                    : null,
                'description' => $desc,
                'photo_path' => $photoPathDb, // null si image absente
            ]);
        }
        }
    }
    /**
     * Génère les stats détaillées à partir des stats de base + position.
     */
    private function buildSkills(array $baseStats, string $position): array
    {
        $speed = $baseStats['speed'];
        $stamina = $baseStats['stamina'];
        $defense = $baseStats['defense'];
        $attack = $baseStats['attack'];

        // valeurs "génériques"
        $shot = (int)round($attack);
        $pass = (int)round($attack * 0.7 + $speed * 0.3);
        $dribble = (int)round($attack * 0.7 + $speed * 0.3);
        $block = (int)round($defense * 0.8 + $stamina * 0.2);
        $intercept = (int)round($defense * 0.7 + $speed * 0.3);
        $tackle = (int)round($defense * 0.9 + $stamina * 0.1);

        // par défaut, les joueurs de champ ne sont pas bons gardiens
        $handSave = max(5, (int)round($defense * 0.2));
        $punchSave = max(5, (int)round($defense * 0.15));

        // Tête : échelle 10-30, hiérarchie DEF > FW > MF > GK (les défenseurs
        // dominent le jeu aérien, les avants pèsent sur les centres)
        $heading = 13;

        switch ($position) {
            case 'Forward':
                $shot = (int)round(min(100, $attack * 1.05));
                $dribble = (int)round(min(100, ($attack * 0.8 + $speed * 0.4) / 1.1));
                $pass = (int)round(($attack * 0.75 + $speed * 0.35) / 1.1);
                $heading = (int)round($attack * 0.15 + $stamina * 0.05 + 10);
                break;

            case 'Midfielder':
                $pass = (int)round(min(100, ($attack * 0.9 + $speed * 0.4) / 1.1));
                $dribble = (int)round(min(100, ($attack * 0.85 + $speed * 0.4) / 1.1));
                $heading = (int)round($defense * 0.12 + $block * 0.05 + $stamina * 0.03 + 10);
                break;

            case 'Defender':
                $block = (int)round(min(100, $block * 1.05));
                $tackle = (int)round(min(100, $tackle * 1.05));
                $intercept = (int)round(($defense * 0.8 + $speed * 0.3) / 1.1);
                $heading = (int)round($defense * 0.18 + $block * 0.06 + $stamina * 0.04 + 12);
                break;

            case 'Goalkeeper':
                // les GK sont spéciaux
                $shot = (int)round($attack * 0.8);
                $pass = (int)round($attack * 0.7 + $speed * 0.3);
                $dribble = (int)round($speed * 0.7 + $defense * 0.3);

                $block = (int)round(($defense * 0.9 + $stamina * 0.3) / 1.2);
                $intercept = (int)round($defense * 0.8 + $speed * 0.2);
                $tackle = (int)round($defense * 0.7 + $stamina * 0.3);

                $handSave = (int)round(min(100, ($defense * 1.3 + $stamina * 0.5) / 1.5));
                $punchSave = (int)round(min(100, ($defense * 1.1 + $stamina * 0.7) / 1.5));
                $heading = (int)round($defense * 0.04 + 10);
                break;
        }

        return $baseStats + [
                'shot' => $shot,
                'pass' => $pass,
                'dribble' => $dribble,
                'block' => $block,
                'intercept' => $intercept,
                'tackle' => $tackle,
                'heading' => max(10, min(30, $heading)),
                'hand_save' => $handSave,
                'punch_save' => $punchSave,
            ];
    }

    /**
     * Overrides manuels de la stat heading pour des joueurs connus
     * (slug "firstname-lastname"), au-delà du calcul automatique.
     */
    private function getHeadingOverride(string $slug): ?int
    {
        // Spécialistes du jeu aérien (échelle de tête : 10-30)
        $overrides = [
            'jito' => 30,
            'soda' => 28,
            'matsuyama' => 26,
        ];

        foreach ($overrides as $needle => $value) {
            if (str_contains($slug, $needle)) {
                return $value;
            }
        }

        return null;
    }
}

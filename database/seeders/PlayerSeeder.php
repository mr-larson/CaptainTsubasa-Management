<?php

namespace Database\Seeders;

use App\Enums\Nationality;
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
     * Nationalité par défaut selon l'œuvre de provenance (origin).
     * Les univers japonais (Captain Tsubasa au collège, Blue Lock, Ao Ashi,
     * Hungry Heart) sont des équipes nippones ; École des Champions est italien
     * majoritaire. Les exceptions sont gérées au cas par cas par les maps
     * nationalities() de chaque classe Players\*. `null` = non sélectionnable.
     */
    private const DEFAULT_NATIONALITY_BY_ORIGIN = [
        'captain_tsubasa'     => 'Japon',
        'blue_lock'           => 'Japon',
        'ao_ashi'             => 'Japon',
        'hungry_heart'        => 'Japon',
        'ecole_des_champions' => 'Italie',
        'original'            => null,
    ];

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
                // Stars étrangères de l'univers Tsubasa (sans contrat club) :
                // disponibles comme agents libres et sélectionnables en équipe
                // nationale via leur `nationality`.
                Players\CaptainTsubasaInternationalPlayers::players(),
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
            Players\CaptainTsubasaInternationalPlayers::specialMoves(),
            Players\EcoleDesChampionsPlayers::specialMoves(),
            Players\HungryHeartPlayers::specialMoves(),
            Players\BlueLockPlayers::specialMoves(),
            Players\AoAshiPlayers::specialMoves(),
            Players\RandomPlayers::specialMoves(),
        );

        // Nationalité par joueur (slug "prenom-nom" → pays). Sert à composer les
        // sélections nationales du mode Coupe du Monde. Résolution :
        //   override par slug  >  défaut par origin (DEFAULT_NATIONALITY_BY_ORIGIN)  >  null
        // Seules les classes ayant des exceptions exposent nationalities().
        $nationalityByPlayerSlug = array_merge(
            Players\EcoleDesChampionsPlayers::nationalities(),
            Players\HungryHeartPlayers::nationalities(),
            Players\CaptainTsubasaInternationalPlayers::nationalities(),
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

            // Nationalité : override par slug, sinon défaut de l'œuvre, sinon null.
            $nationality = $nationalityByPlayerSlug[$slug]
                ?? self::DEFAULT_NATIONALITY_BY_ORIGIN[$origin]
                ?? null;

            // Validation stricte : une nationalité non reconnue (faute de frappe,
            // langue mélangée comme « Sweden » au lieu de « Suède ») fait échouer
            // le seed plutôt que de créer une nation fantôme. Pour un nouveau pays,
            // ajoute-le d'abord dans App\Enums\Nationality.
            if ($nationality !== null && !Nationality::isValid($nationality)) {
                throw new \RuntimeException(
                    "Nationalité inconnue « {$nationality} » pour le joueur « {$slug} ». "
                    . "Ajoute-la dans App\\Enums\\Nationality ou corrige la valeur."
                );
            }

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
                'nationality' => $nationality,
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

        // Tête : échelle 8-30, hiérarchie DEF > FW > MF > GK (les défenseurs
        // dominent le jeu aérien, les avants pèsent sur les centres, les milieux
        // génériques sont moyens et les gardiens très faibles de la tête).
        // La valeur dépend du physique du joueur (defense/attack/stamina) pour
        // étaler les notes au sein d'un même poste plutôt que de tout aplatir.
        $heading = 13;

        switch ($position) {
            case 'Forward':
                $shot = (int)round(min(100, $attack * 1.05));
                $dribble = (int)round(min(100, ($attack * 0.8 + $speed * 0.4) / 1.1));
                $pass = (int)round(($attack * 0.75 + $speed * 0.35) / 1.1);
                $heading = (int)round($attack * 0.18 + $stamina * 0.06 + 7);
                break;

            case 'Midfielder':
                $pass = (int)round(min(100, ($attack * 0.9 + $speed * 0.4) / 1.1));
                $dribble = (int)round(min(100, ($attack * 0.85 + $speed * 0.4) / 1.1));
                $heading = (int)round($defense * 0.18 + $attack * 0.08 + $stamina * 0.05 + 6);
                break;

            case 'Defender':
                $block = (int)round(min(100, $block * 1.05));
                $tackle = (int)round(min(100, $tackle * 1.05));
                $intercept = (int)round(($defense * 0.8 + $speed * 0.3) / 1.1);
                $heading = (int)round($defense * 0.30 + $block * 0.10 + $stamina * 0.06 + 5);
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
                $heading = (int)round($defense * 0.08 + 6);
                break;
        }

        return $baseStats + [
                'shot' => $shot,
                'pass' => $pass,
                'dribble' => $dribble,
                'block' => $block,
                'intercept' => $intercept,
                'tackle' => $tackle,
                'heading' => max(8, min(30, $heading)),
                'hand_save' => $handSave,
                'punch_save' => $punchSave,
            ];
    }

    /**
     * Overrides manuels de la stat heading pour des joueurs connus
     * (slug exact "prenom-nom"), au-delà du calcul automatique.
     *
     * On utilise une correspondance EXACTE (et non str_contains) pour éviter
     * qu'une clé courte ne touche plusieurs joueurs par accident (ex. « soda »
     * matchait Makoto ET Kazuaki Soda). Échelle de tête : 8-30.
     */
    private function getHeadingOverride(string $slug): ?int
    {
        $overrides = [
            // --- Élite du jeu aérien (29-30) ---
            'masao-tachibana'      => 30, // frères Tachibana : tête acrobatique légendaire
            'kazuo-tachibana'      => 30,
            'hiroshi-jito'         => 30, // défenseur, spécialiste des duels aériens
            'makoto-soda'          => 29, // Musashi, mur défensif dominant dans les airs

            // --- Très forts de la tête (25-28) ---
            'kojiro-hyuga'         => 28, // avant-centre puissant et impitoyable
            'hikaru-matsuyama'     => 26, // capitaine de Furano, fort sur les centres
            'kazuaki-soda'         => 25, // défenseur robuste

            // --- Forts / au-dessus de la moyenne (22-24) ---
            'karl-heinz-schneider' => 24, // « Kaiser » allemand, complet et physique
            'hermann-kaltz'        => 24, // arrière allemand, jeu aérien solide
            'juan-diaz'            => 24, // buteur argentin puissant
            'louis-napoleon'       => 23, // libéro français athlétique
            'shingo-takasugi'      => 22, // défenseur solide dans les duels
            'carlos-santana'       => 22, // crack brésilien, complet
            'salvatore-gentile'    => 22, // défenseur italien rugueux
            'shun-nitta'           => 22, // attaquant explosif, bon dans la surface

            // --- Moyens (18-20) ---
            'el-sid-pierre'        => 20, // meneur français athlétique
            'gino-hernandez'       => 20, // ailier physique
            'ryo-ishizaki'         => 20, // défenseur travailleur, généreux dans les duels
            'jun-misugi'           => 19, // « prince du terrain », élégant mais technique
            'teppei-kisugi'        => 18, // attaquant complet de Nankatsu

            // --- Faibles : techniciens / petits gabarits / meneurs au sol (11-14) ---
            'taro-misaki'          => 14, // « magicien du terrain », jeu au sol
            'takeshi-sawada'       => 12, // jeune surdoué technique, petit gabarit
            'tsubasa-ozora'        => 11, // génie technique au sol, faible de la tête
        ];

        return $overrides[$slug] ?? null;
    }
}

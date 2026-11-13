<?php

namespace Database\Seeders;

use App\Helpers\HeadingRating;
use App\Helpers\ReferenceRosterBalance;
use App\Models\Traits\CalculatesWeeklyCost;
use Database\Seeders\Concerns\GuardsReferenceReseed;
use Database\Seeders\Support\HeadingOverrides;
use Database\Seeders\Support\PlayerNationality;
use Database\Seeders\Support\PlayerPhotos;
use Database\Seeders\Support\PlayerSkills;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PlayerSeeder extends Seeder
{
    use GuardsReferenceReseed;
    use CalculatesWeeklyCost;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->refusesProductionReseed('players')) {
            return;
        }

        // On remet la table à zéro avant de reseed
        Schema::disableForeignKeyConstraints();
        DB::table('players')->truncate();
        Schema::enableForeignKeyConstraints();

        // Données joueurs regroupées par source dans database/seeders/Players/.
        // PlayerSeeder compose la ligne `players` ; les calculs vivent dans
        // database/seeders/Support/ (compétences, notes de tête, photos,
        // nationalité). Chaque groupe est tagué par son `origin` (œuvre de
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
                DB::table('players')->insert($this->row(
                    $origin, $player, $nationalityByPlayerSlug, $specialMovesByPlayerSlug,
                ));
            }
        }
    }

    /**
     * Ligne `players` d'un joueur de référence.
     * $player = [prénom, nom, âge, poste, (réservé), stats de base, description?, postes secondaires?].
     *
     * @param array<string, string> $nationalityByPlayerSlug
     * @param array<string, array>  $specialMovesByPlayerSlug
     */
    private function row(string $origin, array $player, array $nationalityByPlayerSlug, array $specialMovesByPlayerSlug): array
    {
        $firstname = $player[0];
        $lastname = $player[1];
        $age = $player[2];
        $position = $player[3];
        $baseStats = $player[5];
        $cost = $this->calculateWeeklyCost($baseStats, $position);
        $desc = $player[6] ?? null;
        // Postes secondaires (optionnel, index 7) : postes où le joueur peut
        // aussi évoluer, avec un bonus de poste réduit en match.
        $secondaryPositions = $player[7] ?? [];

        $fullStats = PlayerSkills::build($baseStats, $position);
        $slug = Str::slug($firstname . ' ' . $lastname); // ex: taro-misaki

        $nationality = PlayerNationality::resolve($slug, $origin, $nationalityByPlayerSlug);

        $headingOverride = HeadingOverrides::for($slug);
        if ($headingOverride !== null) {
            $fullStats['heading'] = $headingOverride;
        }

        [$fullStats, $specialMoves] = ReferenceRosterBalance::apply(
            $origin, trim($firstname . ' ' . $lastname), $position, $fullStats, $specialMovesByPlayerSlug[$slug] ?? [],
        );
        if ($headingOverride === null && !isset($baseStats['heading'])
            && $fullStats['heading'] === HeadingRating::estimate($position, $fullStats)) {
            $fullStats['heading'] = HeadingRating::forProfile($position, $fullStats);
        }

        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'age' => $age,
            'position' => $position,
            'origin' => $origin,
            'nationality' => $nationality,
            'secondary_positions' => json_encode($secondaryPositions, JSON_UNESCAPED_UNICODE),
            'cost' => $cost,
            'stats' => json_encode($fullStats, JSON_UNESCAPED_UNICODE),
            'heading' => $fullStats['heading'],
            'special_moves' => $specialMoves ? json_encode($specialMoves, JSON_UNESCAPED_UNICODE) : null,
            'description' => $desc,
            'photo_path' => PlayerPhotos::pathFor($slug), // null si image absente
        ];
    }
}

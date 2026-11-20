<?php

namespace Database\Seeders;

use App\Helpers\HeadingRating;
use App\Helpers\ReferenceRosterBalance;
use App\Models\Traits\CalculatesWeeklyCost;
use Database\Seeders\Concerns\GuardsReferenceReseed;
use Database\Seeders\Support\HeadingOverrides;
use Database\Seeders\Support\PlayerNationality;
use Database\Seeders\Support\NationalPool;
use Database\Seeders\Support\PlayerPhotos;
use Database\Seeders\Support\PlayerSkills;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Catalogue des joueurs de référence (table `players`) : vide la table, puis insère
 * les joueurs de chaque œuvre (Captain Tsubasa, École des Champions, Hungry Heart,
 * Blue Lock, Ao Ashi, joueurs originaux). Les données sont dans database/seeders/Players/
 * (une classe par groupe ou par club) ; les calculs (stats dérivées, notes de tête,
 * photos, nationalités) dans database/seeders/Support/. Garde-fou en production :
 * voir GuardsReferenceReseed.
 */
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
                Players\CaptainTsubasa\Clubs\Roster::players(),
                Players\CaptainTsubasa\FreePlayers::players(),
                // Stars étrangères de l'univers Tsubasa (sans contrat club) :
                // disponibles comme agents libres et sélectionnables en équipe
                // nationale via leur `nationality`.
                Players\CaptainTsubasa\International\Roster::players(),
            ),
            'ecole_des_champions' => Players\EcoleDesChampions\Roster::players(),
            'hungry_heart'        => Players\HungryHeart\Roster::players(),
            'blue_lock'           => Players\BlueLock\Roster::players(),
            'ao_ashi'             => Players\AoAshi\Roster::players(),
            'original'            => Players\Original\Roster::players(),
            // Dernier groupe pour préserver les ids du catalogue existant.
            'world_legends'       => Players\InternationalLegends\Roster::players(),
        ];

        $specialMovesByPlayerSlug = array_merge(
            Players\CaptainTsubasa\Clubs\Roster::specialMoves(),
            Players\CaptainTsubasa\FreePlayers::specialMoves(),
            Players\CaptainTsubasa\International\Roster::specialMoves(),
            Players\EcoleDesChampions\Roster::specialMoves(),
            Players\HungryHeart\Roster::specialMoves(),
            Players\BlueLock\Roster::specialMoves(),
            Players\AoAshi\Roster::specialMoves(),
            Players\Original\Roster::specialMoves(),
        );

        // Nationalité par joueur (slug "prenom-nom" → pays). Sert à composer les
        // sélections nationales du mode Coupe du Monde. Résolution :
        //   override par slug  >  défaut par origin (DEFAULT_NATIONALITY_BY_ORIGIN)  >  null
        // Seules les classes ayant des exceptions exposent nationalities().
        $nationalityByPlayerSlug = array_merge(
            Players\EcoleDesChampions\Roster::nationalities(),
            Players\HungryHeart\Roster::nationalities(),
            Players\BlueLock\Roster::nationalities(),
            Players\CaptainTsubasa\International\Roster::nationalities(),
            Players\InternationalLegends\Roster::nationalities(),
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
    public function row(string $origin, array $player, array $nationalityByPlayerSlug, array $specialMovesByPlayerSlug): array
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
            'origin' => \App\Enums\PlayerOrigin::forNationality($origin, $nationality),
            'nationality' => $nationality,
            'national_eligible' => NationalPool::eligible(
                \App\Enums\PlayerOrigin::forNationality($origin, $nationality), $nationality, trim($firstname . ' ' . $lastname),
            ),
            'secondary_positions' => json_encode($secondaryPositions, JSON_UNESCAPED_UNICODE),
            'cost' => $cost,
            'stats' => json_encode($fullStats, JSON_UNESCAPED_UNICODE),
            'heading' => $fullStats['heading'],
            'special_moves' => $specialMoves ? json_encode($specialMoves, JSON_UNESCAPED_UNICODE) : null,
            'description' => $desc,
            'photo_path' => PlayerPhotos::pathFor($slug, \App\Enums\PlayerOrigin::forNationality($origin, $nationality)), // null si image absente ou homonyme d'un autre univers
        ];
    }
}

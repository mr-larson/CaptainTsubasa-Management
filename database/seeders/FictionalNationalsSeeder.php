<?php

namespace Database\Seeders;

use App\Enums\Nationality;
use Database\Seeders\Concerns\CalculatesWeeklyCost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Génère des joueurs FICTIFS pour tester le mode Coupe du Monde, sans avoir
 * encore saisi tous les effectifs canon.
 *
 * - N'est PAS appelé par DatabaseSeeder : à lancer manuellement →
 *       php artisan db:seed --class=FictionalNationalsSeeder
 * - Idempotent : purge d'abord tous les joueurs `origin = 'fictional'`.
 * - Top-up : pour chaque nationalité de App\Enums\Nationality, complète
 *   jusqu'à TARGET_SQUAD en COMPTANT les vrais joueurs déjà présents (donc
 *   Japon/Italie, déjà fournis, ne sont pas gonflés).
 * - Stats volontairement modestes (sous les stars canon) ; aucun contrat de
 *   club (joueurs disponibles uniquement pour la sélection nationale).
 *
 * Pour tout supprimer après les tests :
 *       DB::table('players')->where('origin', 'fictional')->delete();
 */
class FictionalNationalsSeeder extends Seeder
{
    use CalculatesWeeklyCost;

    private const ORIGIN       = 'fictional';
    private const TARGET_SQUAD = 16;
    private const AGE          = 13;

    /** Cycle de postes (sur 11) : 1 GK, 4 DEF, 3 MID, 3 FW — démarre par le GK. */
    private const POSITION_CYCLE = [
        'Goalkeeper',
        'Defender', 'Defender', 'Defender', 'Defender',
        'Midfielder', 'Midfielder', 'Midfielder',
        'Forward', 'Forward', 'Forward',
    ];

    private const FIRST_NAMES = [
        'Leo', 'Max', 'Theo', 'Noah', 'Liam', 'Adam', 'Hugo', 'Tom',
        'Ivan', 'Marco', 'Diego', 'Yuki', 'Karl', 'Pablo', 'Nico', 'Sam',
    ];

    public function run(): void
    {
        // 1. Purge des fictifs précédents (idempotence). Aucun contrat ne pointe
        //    vers eux : suppression directe sans risque de FK.
        $purged = DB::table('players')->where('origin', self::ORIGIN)->delete();

        $rows    = [];
        $created = [];

        foreach (Nationality::ALL as $nationality) {
            // Vrais joueurs déjà disponibles pour cette nation (hors fictifs).
            $existing = DB::table('players')
                ->where('nationality', $nationality)
                ->where('origin', '!=', self::ORIGIN)
                ->count();

            $missing = self::TARGET_SQUAD - $existing;
            if ($missing <= 0) {
                continue; // nation déjà fournie (ex. Japon, Italie)
            }

            for ($i = 1; $i <= $missing; $i++) {
                $position  = self::POSITION_CYCLE[($i - 1) % count(self::POSITION_CYCLE)];
                $stats     = $this->buildStats($position);
                $firstname = self::FIRST_NAMES[($i - 1) % count(self::FIRST_NAMES)];
                $lastname  = $nationality . ' ' . $i; // ex. "Brésil 3" → slug unique

                $rows[] = [
                    'firstname'           => $firstname,
                    'lastname'            => $lastname,
                    'age'                 => self::AGE,
                    'position'            => $position,
                    'origin'              => self::ORIGIN,
                    'nationality'         => $nationality,
                    'secondary_positions' => json_encode([], JSON_UNESCAPED_UNICODE),
                    'cost'                => $this->calculateWeeklyCost($stats, $position),
                    'stats'               => json_encode($stats, JSON_UNESCAPED_UNICODE),
                    'special_moves'       => null,
                    'description'         => 'Joueur fictif généré pour tester le mode Coupe du Monde.',
                    'photo_path'          => null,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];
            }

            $created[$nationality] = $missing;
        }

        if ($rows) {
            // Insertion par paquets pour rester léger.
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('players')->insert($chunk);
            }
        }

        $this->command?->info("Fictifs purgés : {$purged}. Générés : " . count($rows) . '.');
        foreach ($created as $nat => $n) {
            $this->command?->line(sprintf('  %s %-16s +%d', Nationality::flag($nat), $nat, $n));
        }
    }

    /**
     * Stats complètes par poste, en bandes aléatoires modestes (sous les stars
     * canon). Clés alignées sur les joueurs existants (+ heading).
     *
     * @return array<string, int>
     */
    private function buildStats(string $position): array
    {
        $r = fn(int $min, int $max): int => random_int($min, $max);

        return match ($position) {
            'Goalkeeper' => [
                'speed' => $r(45, 60), 'stamina' => $r(60, 75), 'defense' => $r(40, 55), 'attack' => $r(12, 20),
                'shot' => $r(10, 16), 'pass' => $r(14, 22), 'dribble' => $r(12, 20),
                'block' => $r(18, 26), 'intercept' => $r(16, 24), 'tackle' => $r(14, 22),
                'hand_save' => $r(28, 42), 'punch_save' => $r(24, 38), 'heading' => $r(10, 14),
            ],
            'Defender' => [
                'speed' => $r(55, 70), 'stamina' => $r(65, 80), 'defense' => $r(32, 45), 'attack' => $r(18, 26),
                'shot' => $r(16, 22), 'pass' => $r(20, 26), 'dribble' => $r(18, 24),
                'block' => $r(26, 34), 'intercept' => $r(24, 32), 'tackle' => $r(26, 34),
                'hand_save' => 10, 'punch_save' => 10, 'heading' => $r(16, 24),
            ],
            'Midfielder' => [
                'speed' => $r(60, 75), 'stamina' => $r(65, 80), 'defense' => $r(22, 30), 'attack' => $r(26, 34),
                'shot' => $r(22, 30), 'pass' => $r(26, 34), 'dribble' => $r(24, 32),
                'block' => $r(18, 24), 'intercept' => $r(20, 26), 'tackle' => $r(20, 26),
                'hand_save' => 10, 'punch_save' => 10, 'heading' => $r(12, 18),
            ],
            default /* Forward */ => [
                'speed' => $r(65, 80), 'stamina' => $r(70, 82), 'defense' => $r(18, 26), 'attack' => $r(32, 42),
                'shot' => $r(28, 36), 'pass' => $r(22, 28), 'dribble' => $r(26, 34),
                'block' => $r(16, 22), 'intercept' => $r(18, 24), 'tackle' => $r(18, 24),
                'hand_save' => 10, 'punch_save' => 10, 'heading' => $r(14, 20),
            ],
        };
    }
}

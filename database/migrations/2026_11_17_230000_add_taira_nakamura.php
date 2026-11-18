<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Une base neuve sera peuplée par PlayerSeeder. Inclure les archives dans
        // la recherche pour préserver les suppressions et retouches manuelles.
        if (! DB::table('players')->exists()
            || DB::table('players')->where('origin', 'ao_ashi')
                ->where('firstname', 'Taira')->where('lastname', 'Nakamura')->exists()) {
            return;
        }

        // Instantané de la référence lors de l'ajout : ne pas réimporter un futur
        // roster ou rééquilibrage à l'exécution de cette migration historique.
        $stats = [
            'speed' => 62, 'stamina' => 72, 'defense' => 30, 'attack' => 23,
            'shot' => 19, 'pass' => 28, 'dribble' => 22,
            'block' => 23, 'intercept' => 27, 'tackle' => 25,
            'hand_save' => 10, 'punch_save' => 10, 'heading' => 18,
        ];
        $moves = [[
            'key' => 'nakamura_composed_distribution',
            'mode' => 'attack',
            'label' => 'Relance maîtrisée',
            'short_label' => 'Relance',
            'cooldown' => 2,
            'base_action' => 'pass',
            'description' => 'Une passe appliquée pour ressortir le ballon et remettre ses partenaires dans le bon sens.',
        ]];
        $photo = 'players/taira-nakamura.png';

        DB::table('players')->insert([
            'firstname' => 'Taira', 'lastname' => 'Nakamura', 'age' => 13,
            'position' => 'Midfielder', 'origin' => 'ao_ashi', 'nationality' => 'Japon',
            'secondary_positions' => json_encode(['Defender']),
            'cost' => 46,
            'stats' => json_encode($stats, JSON_UNESCAPED_UNICODE),
            'heading' => 18,
            'special_moves' => json_encode($moves, JSON_UNESCAPED_UNICODE),
            'description' => 'Milieu de l\'équipe B d\'Esperion, formé au club et aîné d\'Ashito. Attentif à ses coéquipiers, il apporte de l\'équilibre à la récupération et à la relance, et conseille les nouveaux joueurs.',
            'photo_path' => is_file(public_path('storage/'.$photo)) ? $photo : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Conserver la référence : une partie peut déjà l'utiliser.
    }
};

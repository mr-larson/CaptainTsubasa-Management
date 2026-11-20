<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Attache aux personnages internationaux de Captain Tsubasa (Thaïlande, Suède, Allemagne, Angleterre) leur
 * portrait officiel Bandai Namco (World Fighters). Instantané figé dans
 * `database/data/tsubasa-international-bandai-portraits-2026-11-20.json` ; les crédits sont dans
 * `resources/data/player-illustration-credits.json`. Seuls les joueurs sans portrait sont mis à jour,
 * et seulement si le fichier existe : un portrait déjà choisi n'est jamais remplacé.
 */
return new class extends Migration
{
    /** Attache chaque portrait au joueur correspondant. */
    public function up(): void
    {
        $portraits = json_decode(file_get_contents(database_path('data/tsubasa-international-bandai-portraits-2026-11-20.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($portraits as $portrait) {
            if (! is_file(public_path('storage/'.$portrait['photo_path']))) {
                continue;
            }

            DB::table('players')->where('origin', 'captain_tsubasa_international')->whereNull('deleted_at')
                ->where('firstname', $portrait['firstname'])->where('lastname', $portrait['lastname'])
                ->where(fn ($query) => $query->whereNull('photo_path')->orWhere('photo_path', ''))
                ->update(['photo_path' => $portrait['photo_path']]);
        }
    }

    public function down(): void
    {
        // Conserver les références des portraits.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $portraits = json_decode(file_get_contents(database_path('data/transparent-international-portraits-2026-11-18.json')), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($portraits): void {
            foreach ($portraits as $portrait) {
                if (!is_file(public_path('storage/'.$portrait['photo_path']))) continue;

                // Seuls les anciens visuels à fond noir connus sont remplacés,
                // y compris leurs copies en partie. Aucun portrait personnel n'est touché.
                foreach (['players', 'game_players'] as $table) {
                    $query = DB::table($table)->where('origin', 'captain_tsubasa_international')
                        ->where('firstname', $portrait['firstname'])->where('lastname', $portrait['lastname'])
                        ->where('nationality', $portrait['nationality'])
                        ->where('photo_path', $portrait['previous_path']);
                    if ($table === 'players') $query->whereNull('deleted_at');
                    $query->update(['photo_path' => $portrait['photo_path']]);
                }
            }
        });
    }

    public function down(): void
    {
        // Garder les portraits transparents et les choix personnels ultérieurs.
    }
};

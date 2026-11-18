<?php

use Database\Seeders\Support\PlayerPhotos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Liste figée : aucune suppression automatique des futures créations personnelles.
        $removed = [
            ['Hiroki', 'Matsunaga'],
            ['Daiki', 'Kuroiwa'],
            ['Wataru', 'Aoyama'],
            ['Tomoki', 'Fujioka'],
            ['Isamu', 'Terada'],
            ['Haruki', 'Enomoto'],
            ['Yuma', 'Ishikawa'],
            ['Kensuke', 'Hara'],
            ['Shun', 'Sato'],
            ['Hiroto', 'Watanabe'],
            ['Koki', 'Yoshida'],
            ['Takuya', 'Hasegawa'],
            ['Ren', 'Yamashita'],
        ];
        DB::transaction(function () use ($removed) {
            foreach ($removed as [$first, $last]) {
                // Une image ajoutée depuis la préparation du nettoyage protège le profil.
                if (PlayerPhotos::pathFor(Str::slug($first.' '.$last))) continue;
                DB::table('players')->where('origin', 'original')
                    ->where('firstname', $first)->where('lastname', $last)->whereNull('deleted_at')
                    ->where(fn ($q) => $q->whereNull('photo_path')->orWhere('photo_path', ''))
                    ->update(['deleted_at' => now(), 'updated_at' => now()]);
            }
        });
        // Les game_players et les liens existants restent intacts.
    }

    public function down(): void
    {
        // Ne pas réactiver des profils retirés volontairement.
    }
};

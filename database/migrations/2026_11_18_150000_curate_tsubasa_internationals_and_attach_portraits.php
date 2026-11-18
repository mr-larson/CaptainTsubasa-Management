<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $retirements = json_decode(file_get_contents(database_path('data/secondary-internationals-retirement-2026-11-18.json')), true, 512, JSON_THROW_ON_ERROR);
        $portraits = json_decode(file_get_contents(database_path('data/key-international-portraits-2026-11-18.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($retirements, $portraits): void {
            foreach ($retirements as $identity) {
                // Un portrait personnel ajouté entre-temps conserve le joueur dans le catalogue actif.
                DB::table('players')->where($identity)->whereNull('deleted_at')
                    ->where(fn ($q) => $q->whereNull('photo_path')->orWhere('photo_path', ''))
                    ->update(['deleted_at' => now(), 'updated_at' => now()]);
            }
            foreach ($portraits as $portrait) {
                if (!is_file(public_path('storage/'.$portrait['photo_path']))) continue;
                DB::table('players')->where('origin', $portrait['origin'])->whereNull('deleted_at')
                    ->where('firstname', $portrait['firstname'])->where('lastname', $portrait['lastname'])
                    ->where('nationality', $portrait['nationality'])
                    ->where(fn ($q) => $q->whereNull('photo_path')->orWhere('photo_path', '')
                        ->orWhereIn('photo_path', $portrait['previous_paths']))
                    ->update(['photo_path' => $portrait['photo_path']]);
            }
        });
    }

    public function down(): void
    {
        // Ne pas réactiver les archives ni écraser les choix de portraits ultérieurs.
    }
};

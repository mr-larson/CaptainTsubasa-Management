<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $portraits = json_decode(file_get_contents(database_path('data/bandai-international-portraits-2026-11-18.json')), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($portraits): void {
            foreach ($portraits as $portrait) {
                if (!is_file(public_path('storage/'.$portrait['photo_path']))) continue;

                DB::table('players')->where('origin', 'captain_tsubasa_international')->whereNull('deleted_at')
                    ->where('firstname', $portrait['firstname'])->where('lastname', $portrait['lastname'])
                    ->where('nationality', $portrait['nationality'])
                    ->where(fn ($query) => $query->whereNull('photo_path')->orWhere('photo_path', '')
                        ->orWhereIn('photo_path', $portrait['previous_paths']))
                    ->update(['photo_path' => $portrait['photo_path']]);
            }
        });
    }

    public function down(): void
    {
        // Les portraits et les éventuels choix manuels ultérieurs restent conservés.
    }
};

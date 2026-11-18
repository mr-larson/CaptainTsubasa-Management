<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Instantané figé : les futurs ajouts au registre ne changent pas cette migration.
        $portraits = json_decode(file_get_contents(database_path('data/legend-portraits-2026-11-18.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($portraits as $portrait) {
            if (!is_file(public_path('storage/'.$portrait['photo_path']))) continue;

            DB::table('players')->where('origin', 'world_legends')->whereNull('deleted_at')
                ->where('firstname', $portrait['firstname'])->where('lastname', $portrait['lastname'])
                ->where(fn ($query) => $query->whereNull('photo_path')->orWhere('photo_path', '')
                    ->orWhereIn('photo_path', $portrait['previous_paths']))
                ->update(['photo_path' => $portrait['photo_path']]);
        }
    }

    public function down(): void
    {
        // Conserver les références des portraits et les choix ultérieurs de l'utilisateur.
    }
};

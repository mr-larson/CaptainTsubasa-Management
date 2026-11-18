<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function comparable(string $field, mixed $value): mixed
    {
        return in_array($field, ['stats', 'secondary_positions', 'special_moves'], true)
            ? ($value === null ? null : json_decode($value, true, 512, JSON_THROW_ON_ERROR))
            : $value;
    }

    public function up(): void
    {
        $changes = json_decode(file_get_contents(database_path('data/reference-catalogue-update-2026-11-18.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($changes) {
            foreach ($changes['additions'] as $row) {
                // Inclure les archives : une suppression manuelle ne doit pas être annulée.
                if (DB::table('players')->where('origin', $row['origin'])->where('firstname', $row['firstname'])->where('lastname', $row['lastname'])->exists()) continue;
                DB::table('players')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }

            foreach ($changes['updates'] as $change) {
                $query = DB::table('players')->where('origin', $change['origin'])->where('firstname', $change['firstname'])
                    ->where('lastname', $change['lastname'])->whereNull('deleted_at');
                foreach ($query->get() as $player) {
                    // Éviter d'écraser une retouche ultérieure ; les photos ne font jamais partie de ces valeurs.
                    foreach ($change['previous'] as $field => $previous) {
                        if ($this->comparable($field, $player->$field) != $this->comparable($field, $previous)) continue 2;
                    }
                    DB::table('players')->where('id', $player->id)->update($change['values'] + ['updated_at' => now()]);
                }
            }

            foreach ($changes['retirements'] as $player) {
                DB::table('players')->where($player)->whereNull('deleted_at')->update(['deleted_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Les ids et les copies des parties sont conservés ; pas de suppression physique.
    }
};

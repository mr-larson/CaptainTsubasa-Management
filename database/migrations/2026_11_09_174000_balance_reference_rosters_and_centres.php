<?php

use App\Helpers\ReferenceRosterBalance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (['captain_tsubasa', 'ao_ashi'] as $origin) {
            foreach (ReferenceRosterBalance::names($origin) as $name) {
                [$firstname, $lastname] = explode(' ', $name, 2);
                $players = DB::table('players')->where('origin', $origin)
                    ->where('firstname', $firstname)->where('lastname', $lastname)->whereNull('deleted_at')->get();

                foreach ($players as $player) {
                    $stats = json_decode($player->stats ?? '{}', true) ?: [];
                    $moves = json_decode($player->special_moves ?? '[]', true) ?: [];
                    [$updatedStats, $updatedMoves] = ReferenceRosterBalance::apply($origin, $name, $player->position, $stats, $moves);
                    $updates = [];
                    if ($updatedStats !== $stats) {
                        $updates['stats'] = json_encode($updatedStats, JSON_THROW_ON_ERROR);
                        $updates['heading'] = $updatedStats['heading'] ?? $player->heading;
                    }
                    if ($updatedMoves !== $moves) {
                        $updates['special_moves'] = json_encode($updatedMoves, JSON_THROW_ON_ERROR);
                    }
                    if ($updates) DB::table('players')->where('id', $player->id)->update($updates);
                }
            }
        }
    }

    public function down(): void
    {
        // Les profils de référence peuvent avoir été édités depuis la migration.
    }
};

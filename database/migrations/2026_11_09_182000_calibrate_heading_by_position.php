<?php

use App\Helpers\HeadingRating;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $automatic = [];
        $specific = [];

        DB::table('players')->select('id', 'position', 'stats', 'heading')->chunkById(250, function ($players) use (&$automatic, &$specific) {
            foreach ($players as $player) {
                $stats = json_decode($player->stats ?? '{}', true) ?: [];
                $heading = (int) ($stats['heading'] ?? $player->heading);
                if ($heading !== HeadingRating::estimate($player->position, $stats)) {
                    $specific[$player->id] = $heading;
                    continue;
                }

                $calibrated = HeadingRating::forProfile($player->position, $stats);
                $automatic[$player->id] = [$heading, $calibrated, $player->position];
                if ($calibrated === $heading) continue;

                $stats['heading'] = $calibrated;
                DB::table('players')->where('id', $player->id)->update([
                    'heading' => $calibrated,
                    'stats' => json_encode($stats, JSON_THROW_ON_ERROR),
                ]);
            }
        });

        DB::table('game_players')->select('id', 'base_player_id', 'position', 'defense', 'attack', 'block', 'heading')
            ->chunkById(250, function ($players) use ($automatic, $specific) {
                foreach ($players as $player) {
                    $heading = (int) $player->heading;
                    if (($specific[$player->base_player_id] ?? null) === $heading) continue;

                    [$original, $calibrated, $position] = $automatic[$player->base_player_id] ?? [null, null, null];
                    $stats = (array) $player;
                    if ($heading === $original && $player->position === $position) {
                        $updated = $calibrated;
                    } elseif ($heading === HeadingRating::estimate($player->position, $stats)) {
                        $updated = HeadingRating::forProfile($player->position, $stats);
                    } else {
                        continue;
                    }

                    if ($updated !== $heading) {
                        DB::table('game_players')->where('id', $player->id)->where('heading', $heading)
                            ->update(['heading' => $updated]);
                    }
                }
            });

        $this->setDefault(HeadingRating::DEFAULT_FIELD_RATING);
    }

    public function down(): void
    {
        // Les notes peuvent avoir progressé depuis la migration.
        $this->setDefault(25);
    }

    private function setDefault(int $rating): void
    {
        foreach (['players', 'game_players'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($rating) {
                $blueprint->unsignedTinyInteger('heading')->default($rating)->change();
            });
        }
    }
};

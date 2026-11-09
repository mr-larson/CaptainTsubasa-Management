<?php

use App\Helpers\HeadingRating;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('players')->where('heading', 15)->select('id', 'position', 'stats')->chunkById(250, function ($players) {
            foreach ($players as $player) {
                $stats = json_decode($player->stats ?? '{}', true) ?: [];
                $stored = $stats['heading'] ?? null;
                // Les anciennes fiches de référence stockaient la note du seeder dans le JSON.
                // Une note différente de la formule historique correspond à un réglage spécifique.
                $heading = $stored !== null && (int) $stored !== $this->previousRating($player->position, $stats)
                    ? (int) $stored
                    : HeadingRating::estimate($player->position, $stats);
                $stats['heading'] = $heading;
                DB::table('players')->where('id', $player->id)->update(['heading' => $heading, 'stats' => json_encode($stats)]);
            }
        });

        DB::table('game_players')->where('heading', 15)
            ->select('id', 'base_player_id', 'position', 'defense', 'attack', 'block')
            ->chunkById(250, function ($players) {
                $baseRatings = DB::table('players')->whereIn('id', $players->pluck('base_player_id')->filter()->unique())
                    ->pluck('heading', 'id');

                foreach ($players as $player) {
                    $heading = $baseRatings[$player->base_player_id] ?? HeadingRating::estimate($player->position, (array) $player);
                    DB::table('game_players')->where('id', $player->id)->where('heading', 15)->update(['heading' => $heading]);
                }
            });

        $this->setDefault(25);
    }

    private function previousRating(?string $position, array $stats): int
    {
        $defense = (int) ($stats['defense'] ?? 50);
        $attack = (int) ($stats['attack'] ?? 50);
        $stamina = (int) ($stats['stamina'] ?? 50);
        $block = round(min(100, round($defense * 0.8 + $stamina * 0.2) * 1.05));

        $rating = match ($position) {
            'Goalkeeper' => $defense * 0.08 + 6,
            'Defender' => $defense * 0.30 + $block * 0.10 + $stamina * 0.06 + 5,
            'Forward' => $attack * 0.18 + $stamina * 0.06 + 7,
            'Midfielder' => $defense * 0.18 + $attack * 0.08 + $stamina * 0.05 + 6,
            default => 13,
        };

        return max(8, min(30, (int) round($rating)));
    }

    private function setDefault(int $heading): void
    {
        foreach (['players', 'game_players'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($heading) {
                $blueprint->unsignedTinyInteger('heading')->default($heading)->change();
            });
        }
    }

    public function down(): void
    {
        // Les notes ont pu évoluer depuis la migration. Seul le défaut de colonne est réversible.
        $this->setDefault(15);
    }
};

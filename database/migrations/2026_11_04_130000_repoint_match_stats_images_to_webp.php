<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Complément de 2026_11_02_000000_repoint_images_to_webp : les instantanés
 * d'effectifs (match_stats.lineups) et la progression (match_stats.progression)
 * gardaient l'ancien chemin .png des photos, d'où des 404 dans le calendrier.
 * On repointe vers la variante .webp, uniquement quand elle existe.
 */
return new class extends Migration
{
    // Chemin tel qu'il apparaît dans du JSON encodé ("players\/slug.png").
    private const JSON_PATTERN = '#"((?:players|teams)\\\\?/[A-Za-z0-9_-]+)\.(?:png|jpe?g)"#';

    public function up(): void
    {
        DB::table('game_matches')
            ->whereNotNull('match_stats')
            ->where(fn ($q) => $q->where('match_stats', 'like', '%.png%')->orWhere('match_stats', 'like', '%.jp%g%'))
            ->orderBy('id')
            ->chunkById(100, function ($matches) {
                foreach ($matches as $match) {
                    $stats = preg_replace_callback(self::JSON_PATTERN, function ($m) {
                        $slug = str_replace('\\/', '/', $m[1]);

                        return is_file(public_path("storage/{$slug}.webp")) ? '"'.$m[1].'.webp"' : $m[0];
                    }, $match->match_stats);

                    if ($stats !== $match->match_stats) {
                        DB::table('game_matches')->where('id', $match->id)->update(['match_stats' => $stats]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Irréversible : les originaux PNG/JPG ne sont plus servis.
    }
};

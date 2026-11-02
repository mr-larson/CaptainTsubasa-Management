<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les images canon de public/storage/{players,teams} sont désormais servies en
 * WebP redimensionné (cf. `images:publish`) et les anciens PNG/JPG supprimés.
 * Les saves ne stockent que le chemin des images partagées : on repointe donc
 * ces chemins (colonnes + snapshots du state JSON) vers la variante .webp,
 * uniquement quand elle existe.
 */
return new class extends Migration
{
    private const PATTERN = '#^((?:players|teams)/[A-Za-z0-9_-]+)\.(?:png|jpe?g)$#';

    // Même chemin, mais tel qu'il apparaît dans du JSON encodé ("players\/slug.png").
    private const JSON_PATTERN = '#"((?:players|teams)\\\\?/[A-Za-z0-9_-]+)\.(?:png|jpe?g)"#';

    public function up(): void
    {
        foreach ([
            'players'      => 'photo_path',
            'game_players' => 'photo_path',
            'teams'        => 'logo_path',
            'game_teams'   => 'logo_path',
        ] as $table => $column) {
            DB::table($table)
                ->where(fn ($q) => $q->where($column, 'like', 'players/%')->orWhere($column, 'like', 'teams/%'))
                ->select($column)
                ->distinct()
                ->pluck($column)
                ->each(function (string $path) use ($table, $column) {
                    $webp = $this->webpFor($path);
                    if ($webp !== null) {
                        DB::table($table)->where($column, $path)->update([$column => $webp]);
                    }
                });
        }

        DB::table('game_saves')->whereNotNull('state')->orderBy('id')->chunkById(50, function ($saves) {
            foreach ($saves as $save) {
                $state = preg_replace_callback(self::JSON_PATTERN, function ($m) {
                    $webp = $this->webpFor(str_replace('\\/', '/', $m[1]).'.png');

                    return $webp !== null ? '"'.$m[1].'.webp"' : $m[0];
                }, $save->state);

                if ($state !== $save->state) {
                    DB::table('game_saves')->where('id', $save->id)->update(['state' => $state]);
                }
            }
        });
    }

    public function down(): void
    {
        // Irréversible : les originaux PNG/JPG ne sont plus servis.
    }

    private function webpFor(string $path): ?string
    {
        if (! preg_match(self::PATTERN, $path, $m)) {
            return null;
        }

        $webp = $m[1].'.webp';

        return is_file(public_path("storage/{$webp}")) ? $webp : null;
    }
};

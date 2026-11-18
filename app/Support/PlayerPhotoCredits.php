<?php

namespace App\Support;

use App\Enums\PlayerOrigin;
use Illuminate\Support\Facades\DB;

final class PlayerPhotoCredits
{
    public static function all(): array
    {
        return json_decode(file_get_contents(resource_path('data/player-photo-credits.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    /** Complète uniquement les portraits absents du catalogue de référence. */
    public static function attachMissing(): void
    {
        DB::transaction(function () {
            foreach (self::all() as $photo) {
                if (!is_file(public_path('storage/' . $photo['photo_path']))) continue;
                DB::table('players')->where('origin', PlayerOrigin::LEGENDS)
                    ->where('nationality', $photo['nationality'] ?? 'Belgique')
                    ->where('firstname', $photo['firstname'])->where('lastname', $photo['lastname'])
                    ->where(fn ($query) => $query->whereNull('photo_path')->orWhere('photo_path', ''))
                    ->update(['photo_path' => $photo['photo_path']]);
            }
        });
    }
}

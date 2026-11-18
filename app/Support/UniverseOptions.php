<?php

namespace App\Support;

use App\Enums\Nationality;
use App\Enums\PlayerOrigin;
use App\Models\Player;
use Illuminate\Database\Eloquent\Builder;

final class UniverseOptions
{
    /** Effectifs du catalogue à la création, ou de la sauvegarde en gestion. */
    public static function forPlayers(?Builder $query = null): array
    {
        $counts = [];
        $countries = [];
        foreach (($query ?? Player::query())->get(['origin', 'nationality']) as $player) {
            $origin = PlayerOrigin::forNationality($player->origin ?? '', $player->nationality);
            $counts[$origin] = ($counts[$origin] ?? 0) + 1;
            if ($origin === PlayerOrigin::LEGENDS && $player->nationality) {
                $countries[$player->nationality] = ($countries[$player->nationality] ?? 0) + 1;
            }
        }
        ksort($countries);
        return ['counts' => $counts, 'countries' => collect($countries)->map(fn ($count, $name) => [
            'name' => $name, 'flag' => Nationality::flag($name), 'count' => $count,
        ])->values()->all()];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lien d'alchimie entre deux joueurs du catalogue (duo « une-deux »).
 * Le sens a/b n'a pas de signification : un lien est symétrique.
 */
class PlayerLink extends Model
{
    protected $fillable = [
        'player_a_id',
        'player_b_id',
        'label',
    ];

    public function playerA(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_a_id');
    }

    public function playerB(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_b_id');
    }

    /**
     * Carte des partenaires pour un ensemble de joueurs de base :
     * [base_player_id => [['partner_id' => int, 'label' => ?string], …]].
     *
     * Par défaut, seuls les liens dont les DEUX joueurs sont dans $basePlayerIds
     * sont retenus (cas match : le partenaire doit être dans la même équipe).
     * Avec $withinSetOnly=false, tous les liens des joueurs donnés sont
     * retournés (cas draft : montrer le partenaire même s'il n'est pas encore
     * recruté).
     */
    public static function partnerMapFor(array $basePlayerIds, bool $withinSetOnly = true): array
    {
        if (empty($basePlayerIds)) {
            return [];
        }

        $links = static::query()
            ->where(function ($q) use ($basePlayerIds) {
                $q->whereIn('player_a_id', $basePlayerIds)
                  ->orWhereIn('player_b_id', $basePlayerIds);
            })
            ->get();

        if ($withinSetOnly) {
            $set = array_flip($basePlayerIds);
            $links = $links->filter(fn ($l) => isset($set[$l->player_a_id]) && isset($set[$l->player_b_id]));
        }

        $map = [];
        foreach ($links as $link) {
            $map[$link->player_a_id][] = ['partner_id' => $link->player_b_id, 'label' => $link->label];
            $map[$link->player_b_id][] = ['partner_id' => $link->player_a_id, 'label' => $link->label];
        }

        return $map;
    }

    /**
     * Variante UI de partnerMapFor : partenaires avec nom (catalogue), tous
     * liens confondus (le partenaire peut être ailleurs) :
     * [base_player_id => [['partner_base_id' => int, 'partner_name' => string, 'label' => ?string], …]].
     */
    public static function linksWithNamesFor(array $basePlayerIds): array
    {
        $map = static::partnerMapFor($basePlayerIds, withinSetOnly: false);
        if (empty($map)) {
            return [];
        }

        $partnerIds = collect($map)->flatten(1)->pluck('partner_id')->unique()->values();
        $names      = Player::whereIn('id', $partnerIds)->pluck('lastname', 'id');

        $out = [];
        foreach ($map as $baseId => $entries) {
            $withNames = collect($entries)
                ->map(fn ($e) => [
                    'partner_base_id' => $e['partner_id'],
                    'partner_name'    => $names[$e['partner_id']] ?? null,
                    'label'           => $e['label'],
                ])
                ->filter(fn ($e) => $e['partner_name'] !== null)
                ->values()
                ->all();
            if ($withNames) {
                $out[$baseId] = $withNames;
            }
        }

        return $out;
    }
}

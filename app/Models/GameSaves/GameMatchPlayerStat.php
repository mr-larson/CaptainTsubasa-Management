<?php

namespace App\Models\GameSaves;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bilan d'un joueur sur un match (une ligne par joueur et par match).
 *
 * Remplace match_stats.players : les compteurs sont des colonnes, ce qui
 * permet d'agréger une saison en SQL sans décoder le JSON de chaque match.
 * La forme imbriquée historique (offense / defense / duelsWon / duelsLost)
 * reste celle que manipulent les services et le front : voir toStats().
 */
class GameMatchPlayerStat extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    /** Actions offensives : clé de match_stats => préfixe de colonne. */
    public const OFFENSE = [
        'pass'      => 'pass',
        'shot'      => 'shot',
        'dribble'   => 'dribble',
        'special'   => 'special',
        'cross'     => 'cross',
        'long_pass' => 'long_pass',
    ];

    /** Actions défensives : clé de match_stats => préfixe de colonne. */
    public const DEFENSE = [
        'intercept' => 'intercept',
        'tackle'    => 'tackle',
        'block'     => 'block',
        'heading'   => 'heading',
        'hands'     => 'hands',
        'punch'     => 'punch',
        'gkSpecial' => 'gk_special',
    ];

    private const MAX = 65535; // unsignedSmallInteger

    /** Match auquel appartient ce bilan. */
    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'game_match_id');
    }

    /** Toutes les colonnes de compteurs. */
    public static function counterColumns(): array
    {
        $columns = [];
        foreach ([...array_values(self::OFFENSE), ...array_values(self::DEFENSE)] as $prefix) {
            $columns[] = "{$prefix}_attempts";
            $columns[] = "{$prefix}_success";
        }

        return [...$columns, 'goals', 'duels_won', 'duels_lost'];
    }

    /** Forme imbriquée (match_stats.players[id]) => colonnes de compteurs. */
    public static function columnsFromStats(mixed $stats): array
    {
        $stats = is_array($stats) ? $stats : [];
        $int   = fn ($v) => max(0, min(self::MAX, (int) (is_numeric($v) ? $v : 0)));

        $columns = [];
        foreach (['offense' => self::OFFENSE, 'defense' => self::DEFENSE] as $side => $actions) {
            foreach ($actions as $key => $prefix) {
                $columns["{$prefix}_attempts"] = $int($stats[$side][$key]['attempts'] ?? 0);
                $columns["{$prefix}_success"]  = $int($stats[$side][$key]['success'] ?? 0);
            }
        }
        $columns['goals']      = $int($stats['offense']['goals'] ?? 0);
        $columns['duels_won']  = $int($stats['duelsWon'] ?? 0);
        $columns['duels_lost'] = $int($stats['duelsLost'] ?? 0);

        return $columns;
    }

    /** Colonnes de compteurs => forme imbriquée (match_stats.players[id]). */
    public static function statsFromColumns(array|object $row): array
    {
        $row   = (array) $row;
        $stats = ['offense' => [], 'defense' => []];

        foreach (['offense' => self::OFFENSE, 'defense' => self::DEFENSE] as $side => $actions) {
            foreach ($actions as $key => $prefix) {
                $stats[$side][$key] = [
                    'attempts' => (int) ($row["{$prefix}_attempts"] ?? 0),
                    'success'  => (int) ($row["{$prefix}_success"] ?? 0),
                ];
            }
        }
        $stats['offense']['goals'] = (int) ($row['goals'] ?? 0);
        $stats['duelsWon']         = (int) ($row['duels_won'] ?? 0);
        $stats['duelsLost']        = (int) ($row['duels_lost'] ?? 0);

        return $stats;
    }

    /**
     * Bilan de cette ligne dans la forme imbriquée historique (offense / defense /
     * duelsWon / duelsLost).
     */
    public function toStats(): array
    {
        return self::statsFromColumns($this->attributes);
    }
}

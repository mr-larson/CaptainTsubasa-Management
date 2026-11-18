<?php

namespace App\Models;

use App\Enums\Nationality;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Nation du catalogue (mode Coupe du Monde), équivalent de Team pour les clubs.
 *
 * Domaine : tournoi de sélections. Le nom est la valeur de App\Enums\Nationality, identique à
 * `players.nationality` : l'effectif d'une sélection est donc dérivé des joueurs de cette nationalité
 * (NationalTeamAssembler) et non de contrats. Copiée par partie dans `game_teams` (base_nation_id).
 *
 * @property int         $id
 * @property string      $name
 * @property string      $code
 * @property string|null $flag_path
 * @property string      $tactical_style
 * @property string      $management_philosophy
 * @property string|null $default_formation
 * @property bool        $is_active
 */
class Nation extends Model
{
    protected $fillable = [
        'name', 'code', 'flag_path', 'tactical_style', 'management_philosophy', 'default_formation', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    /** Nations proposées au tournoi (les désactivées restent en base mais ne sont plus tirées). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** URL publique du drapeau image, ou null s'il n'y en a pas (repli sur l'emoji). */
    public function getFlagUrlAttribute(): ?string
    {
        return $this->flag_path ? asset($this->flag_path) : null;
    }

    /** Drapeau emoji de la nation (repli quand il n'y a pas d'image). */
    public function getFlagEmojiAttribute(): string
    {
        return Nationality::flag($this->name);
    }
}

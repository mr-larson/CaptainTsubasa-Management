<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use App\Models\Player;
use App\Models\Traits\HasFullName;
use App\Models\Traits\HasPhotoUrl;
use App\Models\Traits\HasSoccerStats;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class GamePlayer extends Model
{
    use HasFactory;
    use HasSoccerStats;
    use HasFullName;
    use HasPhotoUrl;
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id',
        'base_player_id',
        'firstname',
        'lastname',
        'position',
        'origin',
        'nationality',
        'secondary_positions',
        'speed',
        'stamina',
        'attack',
        'defense',
        'shot',
        'pass',
        'dribble',
        'block',
        'intercept',
        'tackle',
        'heading',
        'hand_save',
        'punch_save',
        'cost',
        'morale',
        'coach_affinity',
        'description',
        'photo_path',
        'special_moves'
    ];


    protected $casts = [
        'special_moves' => 'array',
        'secondary_positions' => 'array',
    ];

    /** Origine des joueurs fictifs (générés pour compléter les sélections en Coupe du Monde). */
    public const ORIGIN_FICTIONAL = 'fictional';

    /** Expose le coût avec majoration de polyvalence côté front. */
    protected $appends = ['adjusted_cost'];

    public function basePlayer()
    {
        return $this->belongsTo(Player::class, 'base_player_id');
    }

    // (optionnel mais conseillé) : contrats dans la partie
    public function contracts()
    {
        return $this->hasMany(GameContract::class, 'game_player_id');
    }

    public function moraleLogs()
    {
        return $this->hasMany(GamePlayerMoraleLog::class, 'game_player_id');
    }

    public function promises()
    {
        return $this->hasMany(GamePromise::class, 'game_player_id');
    }

    /**
     * Exclut les joueurs fictifs (origin = 'fictional').
     * Ils ne servent qu'à compléter les sélections nationales en Coupe
     * du Monde et ne doivent jamais apparaître dans le pool de draft.
     */
    public function scopeExcludingFictional($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('origin')
              ->orWhere('origin', '!=', self::ORIGIN_FICTIONAL);
        });
    }

    /**
     * Implémentation pour HasSoccerStats :
     * les stats sont des colonnes directement sur la table game_players.
     */
    protected function getBaseStat(string $key): int
    {
        return (int) ($this->{$key} ?? 0);
    }

    /**
     * Facteur de polyvalence : majoration salariale
     * (+5 % par poste secondaire, plafonnée à +15 %).
     */
    public function versatilityFactor(): float
    {
        $count = count((array) ($this->secondary_positions ?? []));

        return 1.0 + min($count * 0.05, 0.15);
    }

    /** Coût hebdomadaire incluant la majoration de polyvalence. */
    public function getAdjustedCostAttribute(): int
    {
        return (int) round(($this->cost ?? 0) * $this->versatilityFactor());
    }

}

<?php

namespace App\Models\GameSaves;

use App\Models\Player;
use App\Models\Traits\HasSoccerStats;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class GamePlayer extends Model
{
    use HasFactory;
    use HasSoccerStats;

    protected $fillable = [
        'game_save_id',
        'base_player_id',
        'firstname',
        'lastname',
        'position',
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
        'description',
        'photo_path',
        'special_moves'
    ];


    protected $casts = [
        'special_moves' => 'array',
        'secondary_positions' => 'array',
    ];

    /** Expose le coût avec majoration de polyvalence côté front. */
    protected $appends = ['adjusted_cost'];

    public function gameSave()
    {
        return $this->belongsTo(GameSave::class);
    }

    public function basePlayer()
    {
        return $this->belongsTo(Player::class, 'base_player_id');
    }

    // (optionnel mais conseillé) : contrats dans la partie
    public function contracts()
    {
        return $this->hasMany(GameContract::class, 'game_player_id');
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

    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return \Storage::url($this->photo_path);
    }

}

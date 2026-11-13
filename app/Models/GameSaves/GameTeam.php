<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Équipe d'une partie : budget, résultats cumulés de la saison, formation, style
 * tactique et philosophie de gestion. Peut être pilotée par un manager humain
 * (is_controlled, siège human_seat) ou par l'IA. Créée à partir d'une équipe du
 * catalogue (base_team_id) ou sans (Coupe du Monde).
 */
class GameTeam extends Model
{
    use HasFactory;
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id',
        'base_team_id',
        'is_controlled',
        'human_seat',
        'name',
        'description',
        'budget',
        'wins',
        'draws',
        'losses',
        'goals_for',
        'goals_against',
        'logo_path',
        'formation',
        'tactical_style',
        'management_philosophy',
    ];

    protected $casts = [
        'is_controlled' => 'boolean',
        'human_seat'    => 'integer',
    ];

    /** Équipe du catalogue dont celle-ci est la copie de partie (peut être absente). */
    public function baseTeam()
    {
        return $this->belongsTo(Team::class, 'base_team_id');
    }

    /** Contrats de l'équipe dans la partie (actifs ou non). */
    public function contracts()
    {
        return $this->hasMany(GameContract::class);
    }

    /** Joueurs de l'équipe, obtenus à travers leurs contrats. */
    public function players()
    {
        // via les contrats
        return $this->hasManyThrough(
            GamePlayer::class,
            GameContract::class,
            'game_team_id',   // fk sur contracts
            'id',             // pk de game_players
            'id',             // pk de game_teams
            'game_player_id'  // fk sur contracts
        );
    }
}

<?php

namespace App\Models\GameSaves;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'controlled_game_team_id',
        'control_mode',
        'period',
        'season',
        'week',
        'label',
        'state',
        'phase' => 'string',
        'phase',
        'game_mode',
        'competition_type',
    ];


    protected $casts = [
        'state' => 'array',
        'phase' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function matches()
    {
        return $this->hasMany(GameMatch::class);
    }

    public function gameTeams()
    {
        return $this->hasMany(GameTeam::class);
    }

    public function gamePlayers()
    {
        return $this->hasMany(GamePlayer::class);
    }

    public function gameContracts()
    {
        return $this->hasMany(GameContract::class);
    }

    public function controlledGameTeam()
    {
        return $this->belongsTo(GameTeam::class, 'controlled_game_team_id');
    }

    /**
     * Toutes les équipes pilotées par un humain (hot-seat multi-manager),
     * ordonnées par siège de jeu. En mono-joueur, n'en contient qu'une.
     */
    public function controlledGameTeams()
    {
        return $this->hasMany(GameTeam::class)
            ->where('is_controlled', true)
            ->orderByRaw('human_seat IS NULL') // sièges renseignés d'abord
            ->orderBy('human_seat')
            ->orderBy('id');
    }

    /**
     * IDs des équipes humaines de cette sauvegarde.
     *
     * @return array<int>
     */
    public function controlledGameTeamIds(): array
    {
        return $this->controlledGameTeams()->pluck('game_teams.id')->all();
    }
}

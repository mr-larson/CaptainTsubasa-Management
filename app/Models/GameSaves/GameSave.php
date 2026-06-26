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

    public const DEFAULT_CONFIG = [
        'bonus_cards_enabled'       => true,
        'malus_cards_enabled'       => true,
        'match_stamina_cost'        => 5,
        'rest_stamina_recovery'     => 10,
        'match_max_turns'           => 45,
        'injury_on_foul'            => true,
        'suspension_on_3_yellows'   => true,
        'training_max_per_week'     => 3,
        'training_gain_min'         => 1,
        'training_gain_max'         => 5,
        'training_stamina_cost'     => 2,
        'training_min_stamina'      => 10,
        'ai_transfers_enabled'      => true,
        'ai_training_enabled'       => true,
        'visible_origins'           => [
            'captain_tsubasa'     => true,
            'ecole_des_champions' => true,
            'hungry_heart'        => true,
            'blue_lock'           => true,
            'ao_ashi'             => true,
            'original'            => true,
        ],
        'internationals_visible'    => true,
    ];

    public function getConfig(?string $key = null, $default = null)
    {
        $config = array_merge(self::DEFAULT_CONFIG, $this->state['config'] ?? []);
        if ($key === null) {
            return $config;
        }
        return data_get($config, $key, $default ?? data_get(self::DEFAULT_CONFIG, $key));
    }

    public function setConfig(array $values): void
    {
        $state = $this->state ?? [];
        $state['config'] = array_merge($state['config'] ?? [], $values);
        $this->state = $state;
        $this->save();
    }
}

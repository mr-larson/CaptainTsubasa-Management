<?php

namespace App\Models\GameSaves;

use App\Models\Team;
use App\Models\User;
use App\Support\GameConfig;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class GameSave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'controlled_game_team_id',
        'period',
        'season',
        'week',
        'label',
        'state',
        'phase',
        'game_mode',
        'competition_type',
    ];


    protected $casts = [
        'state' => 'array',
        'phase' => 'string',
    ];

    /**
     * Clés du state purement serveur, jamais lues par le front : retirées de la
     * sérialisation (props Inertia, JSON) pour alléger chaque page. La boutique
     * contient notamment les offres de toutes les équipes IA.
     */
    public const SERVER_ONLY_STATE_KEYS = [
        'shop',
        'ai_training_week',
        'season_progression',
        'match_progression_history',
        'training_history',
        // Historique des séances IA (toutes équipes, ~95 Ko en semaine 19) : le
        // front ne lit que la semaine affichée, via game-saves.training.ai-week.
        'ai_training_history',
    ];

    public function toArray()
    {
        $array = parent::toArray();

        if (is_array($array['state'] ?? null)) {
            $array['state'] = Arr::except($array['state'], self::SERVER_ONLY_STATE_KEYS);
        }

        return $array;
    }

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

    /**
     * L'équipe peut-elle être gérée par le joueur qui a la main ?
     * - toujours son équipe (siège actif) ;
     * - les équipes IA si l'option « Accès aux autres clubs » est active ;
     * - jamais celle d'un autre manager humain (hot-seat).
     */
    public function canManageTeam(int $teamId): bool
    {
        if ($teamId === (int) $this->controlled_game_team_id) {
            return true;
        }

        return (bool) $this->getConfig('other_teams_editable')
            && ! in_array($teamId, array_map('intval', $this->controlledGameTeamIds()), true);
    }

    /**
     * Phases pendant lesquelles des matchs se jouent (ligue ou Coupe du Monde).
     * Hors de ces phases (draft, fin de saison, tournoi terminé), terminer un
     * match ou simuler une semaine est refusé.
     */
    public const MATCH_PHASES = ['season', 'world_cup', 'group', 'knockout'];

    public function isInMatchPhase(): bool
    {
        return in_array($this->phase ?? 'season', self::MATCH_PHASES, true);
    }

    public function hasPendingMidseasonBreak(): bool
    {
        $break = $this->state['midseason_break'] ?? [];

        return (int) ($break['return_week'] ?? 0) === (int) $this->week
            && !($break['completed'] ?? true);
    }

    /** Réglages de la partie (défauts, validation, tirages) : voir GameConfig. */
    public function config(): GameConfig
    {
        return GameConfig::for($this);
    }

    /** Raccourci de config()->get() (toute la configuration sans clé). */
    public function getConfig(?string $key = null, $default = null)
    {
        $config = $this->config();

        return $key === null ? $config->all() : $config->get($key, $default);
    }

    public function setConfig(array $values): void
    {
        $state = $this->state ?? [];
        $state['config'] = array_merge($state['config'] ?? [], $values);
        $this->state = $state;
        $this->save();
    }
}

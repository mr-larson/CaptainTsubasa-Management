<?php

namespace App\Models\GameSaves;

use App\Enums\GamePhase;
use App\Models\Team;
use App\Models\User;
use App\Support\GameConfig;
use App\Support\GameState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Une partie (sauvegarde) : saison et semaine courantes, phase, mode de jeu,
 * équipe contrôlée et un `state` JSON pour tout le reste. Les accès au state passent
 * par gameState() (GameState) et les réglages par config() (GameConfig).
 */
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
        'phase' => GamePhase::class,
    ];

    /**
     * Sérialisation de la partie pour les réponses JSON : retire du `state` les clés
     * purement serveur (GameState::SERVER_ONLY_KEYS) pour alléger les pages.
     */
    public function toArray()
    {
        $array = parent::toArray();

        if (is_array($array['state'] ?? null)) {
            $array['state'] = Arr::except($array['state'], GameState::SERVER_ONLY_KEYS);
        }

        return $array;
    }

    /** Utilisateur propriétaire de la partie. */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Équipe du catalogue choisie à la création (mode ligue d'origine). */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /** Matchs de la partie (saison courante uniquement, voir GameMatch). */
    public function matches()
    {
        return $this->hasMany(GameMatch::class);
    }

    /** Équipes de la partie. */
    public function gameTeams()
    {
        return $this->hasMany(GameTeam::class);
    }

    /** Joueurs de la partie (catalogue copié + amateurs + fictifs). */
    public function gamePlayers()
    {
        return $this->hasMany(GamePlayer::class);
    }

    /** Contrats de la partie (saison courante uniquement, voir GameContract). */
    public function gameContracts()
    {
        return $this->hasMany(GameContract::class);
    }

    /** Équipe du manager qui a la main actuellement (en hot-seat, le siège actif). */
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

    /** Phase courante ; une sauvegarde ancienne sans phase est en saison. */
    public function currentPhase(): GamePhase
    {
        return $this->phase ?? GamePhase::Season;
    }

    /** Draft initial ou d'intersaison. */
    public function isDraftPhase(): bool
    {
        return $this->currentPhase()->isDraft();
    }

    /**
     * Phases pendant lesquelles des matchs se jouent (ligue ou Coupe du Monde,
     * cf. GamePhase::matchPhases()). Hors de ces phases (draft, fin de saison,
     * tournoi terminé), terminer un match ou simuler une semaine est refusé.
     */
    public function isInMatchPhase(): bool
    {
        return $this->currentPhase()->isMatchPhase();
    }

    /**
     * La pause de mi-saison est-elle programmée pour la semaine courante et pas encore
     * terminée ?
     */
    public function hasPendingMidseasonBreak(): bool
    {
        return $this->gameState()->hasPendingMidseasonBreak((int) $this->week);
    }

    /** Accès typé au state JSON : voir GameState. */
    public function gameState(): GameState
    {
        return new GameState($this);
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

    /**
     * Enregistre des réglages dans le state (fusionnés avec ceux déjà enregistrés)
     * et sauvegarde. Les valeurs doivent avoir été validées avant (GameConfig::rules).
     */
    public function setConfig(array $values): void
    {
        $state = $this->state ?? [];
        $state['config'] = array_merge($state['config'] ?? [], $values);
        $this->state = $state;
        $this->save();
    }
}

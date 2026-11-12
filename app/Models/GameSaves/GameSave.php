<?php

namespace App\Models\GameSaves;

use App\Helpers\PlayerLuck;
use App\Models\Team;
use App\Models\User;
use App\Services\CareerObjectiveService;
use App\Services\TrainableSpecialMoves;
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
        'phase' => 'string',
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

    public const DRAFT_BUDGETS = ['conquest' => 7000, 'standard' => 8500, 'survival' => 10000, 'none' => 8000];

    public const DEFAULT_CONFIG = [
        'bonus_cards_enabled'       => true,
        'malus_cards_enabled'       => true,
        'match_stamina_cost'        => 5,
        'rest_stamina_recovery'     => 10,
        'match_max_turns'           => 30,
        'match_max_substitutions'   => 5,
        'match_criticals_enabled'   => true,
        'injury_on_foul'            => true,
        'suspension_on_3_yellows'   => true,
        'training_max_per_week'     => 3,
        'training_gain_min'         => 1,
        'training_gain_max'         => 5,
        'training_stamina_cost'     => 2,
        'training_min_stamina'      => 10,
        'training_cost'             => 150,
        'position_training_cost'    => 200,
        'special_training_cost'     => TrainableSpecialMoves::COST,
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
        'career_difficulty'         => 'standard',
        'season_challenges_enabled' => true,
        // Appliqué uniquement à la création du monde (sans effet en cours de partie)
        'initial_morale_random'     => true,
        'initial_stamina_random'    => true,
        'initial_speed_random'      => true,
        // Tirée à la création et au passage à une nouvelle saison.
        'luck_random'               => true,
        // Budget de départ des clubs IA (création du monde uniquement)
        'human_start_budget'        => null, // Par défaut, budget du mandat.
        'ai_start_budget'           => 2500,
        'ai_draft_budget'           => 10000,
        'draft_budget'              => self::DRAFT_BUDGETS['standard'],
        // Accès aux autres clubs : composition des équipes IA modifiable depuis « Autres équipes »
        'other_teams_editable'      => false,
    ];

    // Bornes du moral initial aléatoire : de "mécontent haut" à "satisfait",
    // jamais révolté (refus de re-signer ≤ 20) ni héroïque (> 85) d'entrée.
    public const INITIAL_MORALE_MIN = 45;
    public const INITIAL_MORALE_MAX = 75;

    /**
     * Règles de validation des réglages de partie, partagées par la création
     * (préfixe 'game_config.') et l'écran de gestion (sans préfixe).
     * Les bornes protègent le serveur : match_max_turns borne la boucle de
     * simulation, training_cost ≥ 0 empêche un entraînement « rémunéré ».
     */
    public static function configRules(string $prefix = ''): array
    {
        $rules = [
            'bonus_cards_enabled'     => ['boolean'],
            'malus_cards_enabled'     => ['boolean'],
            'match_stamina_cost'      => ['integer', 'min:0', 'max:20'],
            'rest_stamina_recovery'   => ['integer', 'min:0', 'max:30'],
            'match_max_turns'         => ['integer', 'min:10', 'max:80'],
            'match_max_substitutions' => ['integer', 'min:0', 'max:11'],
            'match_criticals_enabled' => ['boolean'],
            'injury_on_foul'          => ['boolean'],
            'suspension_on_3_yellows' => ['boolean'],
            'training_max_per_week'   => ['integer', 'min:1', 'max:10'],
            'training_gain_min'       => ['integer', 'min:0', 'max:10'],
            'training_gain_max'       => ['integer', 'min:1', 'max:20'],
            'training_stamina_cost'   => ['integer', 'min:0', 'max:15'],
            'training_min_stamina'    => ['integer', 'min:0', 'max:50'],
            'training_cost'           => ['integer', 'min:0', 'max:5000'],
            'position_training_cost'  => ['integer', 'min:0', 'max:5000'],
            'special_training_cost'   => ['integer', 'min:0', 'max:2000'],
            'ai_transfers_enabled'    => ['boolean'],
            'ai_training_enabled'     => ['boolean'],
            'visible_origins'         => ['array'],
            'visible_origins.*'       => ['boolean'],
            'internationals_visible'  => ['boolean'],
            'career_difficulty'       => ['string', 'in:none,survival,standard,conquest'],
            'season_challenges_enabled' => ['boolean'],
            'initial_morale_random'   => ['boolean'],
            'initial_stamina_random'  => ['boolean'],
            'initial_speed_random'    => ['boolean'],
            'luck_random'             => ['boolean'],
            'ai_start_budget'         => ['integer', 'min:0', 'max:20000'],
            'human_start_budget'      => ['integer', 'min:0', 'max:20000'],
            'ai_draft_budget'         => ['integer', 'min:3000', 'max:20000'],
            'draft_budget'            => ['integer', 'min:3000', 'max:20000'],
            'other_teams_editable'    => ['boolean'],
        ];

        $prefixed = [];
        foreach ($rules as $key => $rule) {
            $prefixed[$prefix . $key] = $rule;
        }

        return $prefixed;
    }

    /**
     * Ne garde que les réglages connus (clés de DEFAULT_CONFIG, et origines
     * connues pour visible_origins) : rien d'arbitraire ne finit dans le state.
     */
    public static function sanitizeConfig(?array $config): array
    {
        if (empty($config)) {
            return [];
        }

        $clean = Arr::only($config, array_keys(self::DEFAULT_CONFIG));
        if (isset($clean['visible_origins']) && is_array($clean['visible_origins'])) {
            $clean['visible_origins'] = Arr::only(
                $clean['visible_origins'],
                array_keys(self::DEFAULT_CONFIG['visible_origins']),
            );
        }

        return $clean;
    }

    /**
     * Gain min ≤ gain max (sinon random_int lève une ValueError), en
     * complétant le champ absent par la valeur déjà en vigueur.
     */
    public function hasValidTrainingGains(array $values): bool
    {
        $min = $values['training_gain_min'] ?? (int) $this->getConfig('training_gain_min', config('training.gain_min', 1));
        $max = $values['training_gain_max'] ?? (int) $this->getConfig('training_gain_max', config('training.gain_max', 5));

        return (int) $min <= (int) $max;
    }

    public const INITIAL_SPEED_VARIATION = 10;

    public function initialSpeed(int $baseSpeed, ?int $variation = null): int
    {
        if (!$this->getConfig('initial_speed_random')) {
            return $baseSpeed;
        }

        $variation ??= random_int(-self::INITIAL_SPEED_VARIATION, self::INITIAL_SPEED_VARIATION);

        return max(0, min(100, $baseSpeed + $variation));
    }

    public function initialStamina(): int
    {
        return $this->getConfig('initial_stamina_random') ? random_int(60, 100) : 100;
    }

    public function rollPlayerLuck(): int
    {
        return $this->getConfig('luck_random') ? PlayerLuck::roll() : PlayerLuck::NEUTRAL;
    }

    public function getConfig(?string $key = null, $default = null)
    {
        $saved = $this->state['config'] ?? [];
        $config = array_merge(self::DEFAULT_CONFIG, $saved);
        $config['draft_budget'] = $saved['draft_budget'] ?? self::DRAFT_BUDGETS[$config['career_difficulty']] ?? self::DEFAULT_CONFIG['draft_budget'];
        $config['human_start_budget'] = $saved['human_start_budget']
            ?? CareerObjectiveService::PRESETS[$config['career_difficulty']]['start_budget']
            ?? CareerObjectiveService::SANDBOX_START_BUDGET;
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

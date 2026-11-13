<?php

namespace App\Support;

use App\Helpers\PlayerLuck;
use App\Models\GameSaves\GameSave;
use App\Services\CareerObjectiveService;
use App\Services\TrainableSpecialMoves;
use Illuminate\Support\Arr;

/**
 * Réglages d'une partie (state['config'] de GameSave) : valeurs par défaut,
 * règles de validation, nettoyage, lecture typée et tirages dépendant des options.
 *
 * Instance = les réglages d'UNE partie (GameSave::config()). Les parties
 * statiques (rules, sanitize, DEFAULTS) servent à la création, avant que la
 * partie n'existe.
 */
final class GameConfig
{
    public const DRAFT_BUDGETS = ['conquest' => 7000, 'standard' => 8500, 'survival' => 10000, 'none' => 8000];

    public const DEFAULTS = [
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

    public const INITIAL_SPEED_VARIATION = 10;

    /** @var array<string, mixed> Réglages enregistrés (sans les défauts). */
    private array $saved;

    /** @param array<string, mixed> $saved */
    public function __construct(array $saved = [])
    {
        $this->saved = $saved;
    }

    /** Réglages de la sauvegarde donnée, lus dans son state['config']. */
    public static function for(GameSave $save): self
    {
        return new self($save->state['config'] ?? []);
    }

    // ── Lecture ───────────────────────────────────────────────────────

    /** Tous les réglages, défauts et valeurs dérivées de la difficulté compris. */
    public function all(): array
    {
        $config = array_merge(self::DEFAULTS, $this->saved);
        $config['draft_budget'] = $this->saved['draft_budget']
            ?? self::DRAFT_BUDGETS[$config['career_difficulty']]
            ?? self::DEFAULTS['draft_budget'];
        $config['human_start_budget'] = $this->saved['human_start_budget']
            ?? CareerObjectiveService::PRESETS[$config['career_difficulty']]['start_budget']
            ?? CareerObjectiveService::SANDBOX_START_BUDGET;

        return $config;
    }

    /** Valeur d'un réglage (notation pointée) ; à défaut, la valeur par défaut de DEFAULTS. */
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default ?? data_get(self::DEFAULTS, $key));
    }

    /** Réglage lu comme booléen. */
    public function bool(string $key): bool
    {
        return (bool) $this->get($key);
    }

    /** Réglage lu comme entier. */
    public function int(string $key, ?int $default = null): int
    {
        return (int) $this->get($key, $default);
    }

    /** Réglage lu comme chaîne. */
    public function string(string $key): string
    {
        return (string) $this->get($key);
    }

    /** Nouvelle configuration avec ces valeurs en plus (les valeurs enregistrées sont complétées, pas remplacées). */
    public function with(array $values): self
    {
        return new self(array_merge($this->saved, $values));
    }

    /** Réglages enregistrés tels quels (ce qui est stocké dans state['config']). */
    public function saved(): array
    {
        return $this->saved;
    }

    // ── Validation ────────────────────────────────────────────────────

    /**
     * Règles de validation des réglages de partie, partagées par la création
     * (préfixe 'game_config.') et l'écran de gestion (sans préfixe).
     * Les bornes protègent le serveur : match_max_turns borne la boucle de
     * simulation, training_cost ≥ 0 empêche un entraînement « rémunéré ».
     */
    public static function rules(string $prefix = ''): array
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
     * Ne garde que les réglages connus (clés de DEFAULTS, et origines connues
     * pour visible_origins) : rien d'arbitraire ne finit dans le state.
     */
    public static function sanitize(?array $config): array
    {
        if (empty($config)) {
            return [];
        }

        $clean = Arr::only($config, array_keys(self::DEFAULTS));
        if (isset($clean['visible_origins']) && is_array($clean['visible_origins'])) {
            $clean['visible_origins'] = Arr::only(
                $clean['visible_origins'],
                array_keys(self::DEFAULTS['visible_origins']),
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
        $min = $values['training_gain_min'] ?? $this->int('training_gain_min', config('training.gain_min', 1));
        $max = $values['training_gain_max'] ?? $this->int('training_gain_max', config('training.gain_max', 5));

        return (int) $min <= (int) $max;
    }

    // ── Tirages dépendant des options ─────────────────────────────────

    /**
     * Vitesse de départ d'un joueur : base inchangée, ou base + variation aléatoire (bornée 0-100) si l'option est active.
     */
    public function initialSpeed(int $baseSpeed, ?int $variation = null): int
    {
        if (! $this->bool('initial_speed_random')) {
            return $baseSpeed;
        }

        $variation ??= random_int(-self::INITIAL_SPEED_VARIATION, self::INITIAL_SPEED_VARIATION);

        return max(0, min(100, $baseSpeed + $variation));
    }

    /** Endurance de départ : 100, ou tirage entre 60 et 100 si l'option est active. */
    public function initialStamina(): int
    {
        return $this->bool('initial_stamina_random') ? random_int(60, 100) : 100;
    }

    /** Chance de départ : tirage de PlayerLuck si l'option est active, sinon valeur neutre. */
    public function rollLuck(): int
    {
        return $this->bool('luck_random') ? PlayerLuck::roll() : PlayerLuck::NEUTRAL;
    }
}

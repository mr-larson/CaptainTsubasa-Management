<?php

namespace App\Support;

use App\Models\GameSaves\GameSave;
use App\Support\StateConcerns\HasLineupState;
use App\Support\StateConcerns\HasSeasonCycleState;
use App\Support\StateConcerns\HasDraftState;
use App\Support\StateConcerns\HasChallengesState;
use App\Support\StateConcerns\HasTrainingState;
use App\Support\StateConcerns\HasProgressionState;
use App\Support\StateConcerns\HasCardState;
use App\Support\StateConcerns\HasCompetitionState;

/**
 * Accès typé au `state` JSON d'une sauvegarde (GameSave::gameState()).
 *
 * Chaque clé migrée a ses accesseurs ici : son nom, sa forme et sa valeur par
 * défaut ne vivent qu'à un endroit, les services n'écrivent plus `['lineup'][$id]['slots']`
 * à la main. La forme stockée est INCHANGÉE (aucune migration de données).
 *
 * Les écritures modifient le state en mémoire ; `save()` (ou `$gameSave->save()`)
 * persiste. Ne remplace pas le verrou de save : une écriture reste un
 * lecture-modification-écriture de tout le JSON (voir LocksGameSave).
 *
 * Les accesseurs sont rangés par domaine dans app/Support/StateConcerns/ (compositions,
 * cycle de saison, draft, défis, entraînement, progression, cartes, compétition) ;
 * cette classe porte l'accès brut au JSON et les clés serveur. Toutes les clés ont leurs accesseurs : lineup, ai_rotation, midseason_break,
 * season_history / last_season_recap, free_agent_signings, declaration_match_boosts,
 * draft, challenges, training, training_history, ai_training_history, ai_training_week,
 * season_progression, match_progression_history, cartes (active_pre_match_cards,
 * pending_stat_debuffs, pending_malus, malus_shields, pending_sponsor_challenges,
 * sponsor_results), shop, tournament, career, contract_departures.
 * Seule `config` est à part : voir GameConfig. Une nouvelle clé s'ajoute ICI, avec sa
 * forme documentée, et ne se lit pas par chaîne brute ailleurs.
 */
final class GameState
{
    use HasLineupState;
    use HasSeasonCycleState;
    use HasDraftState;
    use HasChallengesState;
    use HasTrainingState;
    use HasProgressionState;
    use HasCardState;
    use HasCompetitionState;

    /**
     * Clés du state purement serveur, jamais lues par le front : retirées de la
     * sérialisation (props Inertia, JSON) pour alléger chaque page. La boutique
     * contient notamment les offres de toutes les équipes IA.
     */
    public const SERVER_ONLY_KEYS = [
        'shop',
        'ai_training_week',
        'season_progression',
        'match_progression_history',
        'training_history',
        // Historique des séances IA (toutes équipes, ~95 Ko en semaine 19) : le
        // front ne lit que la semaine affichée, via game-saves.training.ai-week.
        'ai_training_history',
    ];

    /** Enveloppe le state d'une sauvegarde. Obtenir une instance par $gameSave->gameState(). */
    public function __construct(private GameSave $save)
    {
    }

    /** Persiste la sauvegarde (donc son state modifié en mémoire). */
    public function save(): void
    {
        $this->save->save();
    }

    // ── Accès brut (clés pas encore migrées) ──────────────────────────

    /** Le state complet sous forme de tableau (vide si la sauvegarde n'en a pas). */
    public function raw(): array
    {
        return $this->save->state ?? [];
    }

    /** Valeur d'une clé du state, ou la valeur par défaut si elle est absente. */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw()[$key] ?? $default;
    }

    /** Écrit une clé du state, en mémoire seulement (appeler save() ensuite). */
    public function put(string $key, mixed $value): void
    {
        $state = $this->raw();
        $state[$key] = $value;
        $this->save->state = $state;
    }

    /** Supprime une ou plusieurs clés du state, en mémoire seulement (appeler save() ensuite). */
    public function forget(string ...$keys): void
    {
        $state = $this->raw();
        foreach ($keys as $key) {
            unset($state[$key]);
        }
        $this->save->state = $state;
    }
}

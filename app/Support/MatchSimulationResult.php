<?php

namespace App\Support;

/**
 * Résultat de la demande « simuler mon prochain match », renvoyé par
 * MatchSimulationService au contrôleur.
 *
 * Trois issues possibles :
 *   - redirect()  : rien n'est simulé (le match n'est plus à jouer, la partie est
 *                   terminée…) ; on se contente d'aller à l'écran indiqué ;
 *   - forbidden() : la demande est interdite (403) ;
 *   - simulated() : le match a été simulé ; on va à l'écran suivant avec un résumé du score.
 *
 * Le service ne fabrique aucune réponse HTTP : le contrôleur traduit ce résultat.
 */
final class MatchSimulationResult
{
    /**
     * @param string      $outcome          écran cible, une constante WeekClosingService::OUTCOME_*
     * @param string|null $summary          score affichable (« Équipe A 2–1 Équipe B »), seulement si simulé
     * @param string|null $forbiddenMessage motif du refus (403), seulement si interdit
     */
    private function __construct(
        public readonly string $outcome,
        public readonly ?string $summary = null,
        public readonly ?string $forbiddenMessage = null,
    ) {
    }

    /** Rien n'a été simulé : on va simplement à l'écran indiqué. */
    public static function redirect(string $outcome): self
    {
        return new self($outcome);
    }

    /** Demande interdite (403), avec le motif à afficher. */
    public static function forbidden(string $message): self
    {
        return new self('', null, $message);
    }

    /** Match simulé : écran suivant et résumé du score. */
    public static function simulated(string $outcome, string $summary): self
    {
        return new self($outcome, $summary);
    }

    public function isForbidden(): bool
    {
        return $this->forbiddenMessage !== null;
    }
}

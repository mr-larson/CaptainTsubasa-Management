<?php

namespace App\Support;

/**
 * Résultat d'une action de draft, renvoyé par DraftTurnService au contrôleur.
 *
 * Il contient le corps de la réponse JSON et son code HTTP, sans que le service
 * ait à fabriquer lui-même une réponse : le contrôleur n'a plus qu'à écrire
 * `response()->json($result->body, $result->status)`.
 */
final class DraftTurnResult
{
    private function __construct(
        public readonly array $body,
        public readonly int $status = 200,
    ) {
    }

    /** Action réussie : corps de la réponse (pick, état du draft, draft terminé ou non…). */
    public static function ok(array $body): self
    {
        return new self($body);
    }

    /**
     * Action refusée.
     *
     * @param int $status 400 = demande incohérente avec l'état du draft ; 422 = choix impossible
     */
    public static function error(string $message, int $status = 400): self
    {
        return new self(['error' => $message], $status);
    }
}

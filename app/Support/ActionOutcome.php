<?php

namespace App\Support;

/**
 * Résultat d'une action métier, renvoyé par un service au contrôleur.
 *
 * Un service ne doit pas fabriquer de réponse HTTP : il dit simplement ce qui
 * s'est passé, et le contrôleur le traduit en redirection (message de succès,
 * d'information, ou erreur rattachée à un champ du formulaire).
 *
 * Trois cas possibles :
 *   - success : l'action a eu lieu ;
 *   - info    : rien n'a été fait, mais ce n'est pas une faute (ex. « déjà fait ») ;
 *   - error   : l'action est refusée pour une raison que l'utilisateur peut corriger.
 */
final class ActionOutcome
{
    public const SUCCESS = 'success';
    public const INFO    = 'info';
    public const ERROR   = 'error';

    /** Constructeur privé : on passe par les fabriques success(), info() et error(). */
    private function __construct(
        public readonly string $level,
        public readonly string $message,
        public readonly ?string $field = null,
    ) {
    }

    /** L'action a été réalisée. */
    public static function success(string $message): self
    {
        return new self(self::SUCCESS, $message);
    }

    /** Rien à faire, sans que ce soit une erreur. */
    public static function info(string $message): self
    {
        return new self(self::INFO, $message);
    }

    /**
     * Action refusée.
     *
     * @param string $field champ du formulaire auquel rattacher le message d'erreur
     */
    public static function error(string $field, string $message): self
    {
        return new self(self::ERROR, $message, $field);
    }

    /** Vrai si l'action a été refusée. */
    public function isError(): bool
    {
        return $this->level === self::ERROR;
    }
}

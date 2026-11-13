<?php

namespace App\Models\GameSaves\Concerns;

/**
 * Relances du capitaine pendant un match : un capitaine dispose d'un petit stock
 * de relances (3 par match) et d'une seule par action. Ces compteurs vivent sur le
 * contrat du capitaine (`captain_rerolls_remaining`,
 * `captain_reroll_used_this_action`) et sont remis à zéro à chaque match.
 *
 * Le modèle qui utilise ce trait doit avoir les colonnes `is_captain`,
 * `captain_rerolls_remaining` et `captain_reroll_used_this_action`.
 */
trait ManagesCaptainRerolls
{
    /**
     * Le capitaine peut-il relancer en ce moment ?
     * Conditions : est capitaine + relances restantes + pas encore utilisé cette action.
     */
    public function canUseReroll(): bool
    {
        return $this->is_captain
            && $this->captain_rerolls_remaining > 0
            && ! $this->captain_reroll_used_this_action;
    }

    /**
     * Consomme une relance et pose le drapeau « utilisé cette action ».
     * Retourne false (sans rien modifier) si la relance est impossible.
     */
    public function useReroll(): bool
    {
        if (! $this->canUseReroll()) {
            return false;
        }

        $this->captain_rerolls_remaining--;
        $this->captain_reroll_used_this_action = true;
        $this->save();

        return true;
    }

    /**
     * Remet à zéro le drapeau « utilisé cette action » (appelé au début de chaque
     * nouvelle action du match). N'écrit en base que s'il était levé.
     */
    public function resetRerollActionFlag(): void
    {
        if ($this->captain_reroll_used_this_action) {
            $this->captain_reroll_used_this_action = false;
            $this->save();
        }
    }

    /** Remise à zéro complète pour un nouveau match : 3 relances et drapeau baissé. */
    public function resetForNewMatch(): void
    {
        $this->captain_rerolls_remaining       = 3;
        $this->captain_reroll_used_this_action = false;
        $this->save();
    }

    /**
     * Statut des relances pour l'interface du match : identifiant du contrat,
     * capitaine ou non, relances restantes, possibilité de relancer, et si une
     * relance a déjà été utilisée pour l'action en cours.
     *
     * @return array<string, mixed>
     */
    public function captainRerollStatus(): array
    {
        return [
            'contractId'       => $this->id,
            'isCaptain'        => $this->is_captain,
            'rerollsRemaining' => $this->captain_rerolls_remaining,
            'canReroll'        => $this->canUseReroll(),
            'usedThisAction'   => $this->captain_reroll_used_this_action,
        ];
    }
}

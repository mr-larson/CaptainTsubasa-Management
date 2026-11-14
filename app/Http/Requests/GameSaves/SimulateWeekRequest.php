<?php

namespace App\Http\Requests\GameSaves;

/** Requête de simulation de la semaine courante (matchs IA puis clôture de la semaine). */
class SimulateWeekRequest extends GameSaveActionRequest
{
    /**
     * Semaine affichée par le navigateur (facultative : sert à ignorer un double
     * clic ou une page périmée) et validation éventuelle de la pause de mi-saison.
     */
    public function rules(): array
    {
        return [
            'week'            => ['nullable', 'integer', 'min:1'],
            'midseason_break' => ['nullable', 'boolean'],
        ];
    }

    /** Semaine affichée par le navigateur, ou null si elle n'a pas été envoyée. */
    public function displayedWeek(): ?int
    {
        $week = $this->validated('week');

        return $week === null ? null : (int) $week;
    }

    /** Vrai si le joueur valide la pause de mi-saison. */
    public function confirmsMidseasonBreak(): bool
    {
        return (bool) $this->validated('midseason_break');
    }
}

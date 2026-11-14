<?php

namespace App\Services\Cards\Effects;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameSave;

/**
 * Un effet de carte activée : applique l'effet puis renvoie le résultat affiché au joueur
 * (au minimum une clé « message »). Lève une ValidationException si l'activation est refusée.
 * Le marquage « carte utilisée » reste à la charge de BonusCardActivationService.
 */
interface CardEffect
{
    /**
     * @param  int|null  $targetPlayerId  joueur visé (cartes target=player)
     * @param  int|null  $targetTeamId    équipe visée (malus choisissant une équipe)
     * @return array<string, mixed>
     */
    public function apply(
        GameSave $gameSave,
        GameBonusCard $gameBonusCard,
        ?int $targetPlayerId = null,
        ?int $targetTeamId = null,
    ): array;
}

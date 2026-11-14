<?php

namespace App\Services\Cards\Effects;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameSave;
use App\Services\Cards\MalusCardEffects;

/** Adaptateur : délègue une carte malus (ou bouclier) à la méthode correspondante de MalusCardEffects. */
class MalusEffect implements CardEffect
{
    public function __construct(private MalusCardEffects $malus, private string $method)
    {
    }

    public function apply(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId = null, ?int $targetTeamId = null): array
    {
        // Le bouclier (applyMalusShield) n'a pas de cible : l'argument en trop est ignoré.
        return $this->malus->{$this->method}($gameSave, $gameBonusCard, $targetTeamId);
    }
}

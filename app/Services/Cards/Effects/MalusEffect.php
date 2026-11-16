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
        // Le bouclier et le piège n'ont pas de cible : l'argument en trop est ignoré.
        $result = $this->malus->{$this->method}($gameSave, $gameBonusCard, $targetTeamId);

        // Malus renvoyé par un piège : l'expéditeur l'a subi lui-même, on le lui dit.
        if ($trapOwner = $this->malus->pullReflection()) {
            $result['message']   = "🍌 {$trapOwner} avait posé un piège : le malus vous est renvoyé ! " . ($result['message'] ?? '');
            $result['reflected'] = true;
        }

        return $result;
    }
}

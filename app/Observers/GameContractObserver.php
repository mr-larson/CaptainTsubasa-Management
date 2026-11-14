<?php

namespace App\Observers;

use App\Models\GameSaves\GameContract;
use App\Services\Season\SeasonChallengeService;

/**
 * Réagit aux événements d'un contrat de partie. Enregistré sur le modèle par
 * l'attribut #[ObservedBy] de GameContract.
 */
class GameContractObserver
{
    /**
     * À la signature d'un contrat : compte une recrue pour le défi de saison
     * « Recruter X joueurs » (une recrue repartie ensuite reste comptée).
     * Ne s'exécute pas pour les insertions de masse (`GameContract::insert`), qui
     * contournent les événements : c'est voulu à la création d'une partie, où le
     * défi n'existe pas encore.
     */
    public function created(GameContract $contract): void
    {
        SeasonChallengeService::recordSigning($contract);
    }
}

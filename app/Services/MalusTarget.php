<?php

namespace App\Services;

use App\Models\GameSaves\GameTeam;

/**
 * Équipe visée par une carte malus, pour la durée d'UNE activation.
 *
 * L'identifiant suffit à appliquer la plupart des effets, mais le message affiché (et le malus
 * de budget) a besoin de l'équipe elle-même. Elle n'est lue qu'à la demande et une seule fois :
 * quand la cible a été trouvée en parcourant toutes les équipes (le « leader »), elle est déjà
 * connue et aucune lecture supplémentaire n'est faite.
 */
final class MalusTarget
{
    private ?GameTeam $team;
    private bool $loaded;

    /** @param GameTeam|null $team l'équipe si l'appelant l'a déjà chargée, sinon null (lecture à la demande) */
    public function __construct(public readonly int $id, ?GameTeam $team = null)
    {
        $this->team   = $team;
        $this->loaded = $team !== null;
    }

    /** L'équipe cible, ou null si elle n'existe plus. Lue en base au premier appel seulement. */
    public function team(): ?GameTeam
    {
        if (! $this->loaded) {
            $this->team   = GameTeam::find($this->id);
            $this->loaded = true;
        }

        return $this->team;
    }
}

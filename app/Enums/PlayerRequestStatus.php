<?php

namespace App\Enums;

/** Statut d'une demande formulée par un joueur (game_player_requests.status). */
enum PlayerRequestStatus: string
{
    case Pending  = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    /** Laissée sans réponse jusqu'à la clôture de la semaine. */
    case Ignored  = 'ignored';
}

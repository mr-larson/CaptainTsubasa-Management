<?php

namespace App\Enums;

/** Statut d'une promesse faite à un joueur (game_promises.status). */
enum PromiseStatus: string
{
    case Pending = 'pending';
    case Kept    = 'kept';
    case Broken  = 'broken';
    /** Promesse devenue sans objet (joueur parti, fin de saison). */
    case Expired = 'expired';
}

<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\User;

/**
 * Données de référence (roster canonique) : consultation comme modification
 * sont réservées aux administrateurs, comme les routes qui y mènent (middleware
 * `admin`). Les joueurs ne voient ces données qu'à travers leurs parties.
 */
class PlayerPolicy
{
    /** Consultation de la liste réservée aux administrateurs (écrans d'administration). */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Consultation de ce joueur réservée aux administrateurs. */
    public function view(User $user, Player $player): bool
    {
        return $user->isAdmin();
    }

    /** Création réservée aux administrateurs. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Modification de ce joueur réservée aux administrateurs. */
    public function update(User $user, Player $player): bool
    {
        return $user->isAdmin();
    }

    /** Suppression de ce joueur réservée aux administrateurs. */
    public function delete(User $user, Player $player): bool
    {
        return $user->isAdmin();
    }
}

<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\User;

/**
 * Données de référence (roster canonique). La consultation est ouverte aux
 * utilisateurs connectés ; toute modification est réservée aux administrateurs.
 */
class PlayerPolicy
{
    /** Tout utilisateur connecté peut consulter la liste. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Tout utilisateur connecté peut consulter ce joueur. */
    public function view(User $user, Player $player): bool
    {
        return true;
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

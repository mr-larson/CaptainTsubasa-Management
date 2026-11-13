<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

/**
 * Données de référence (roster canonique). La consultation est ouverte aux
 * utilisateurs connectés ; toute modification est réservée aux administrateurs.
 */
class TeamPolicy
{
    /** Tout utilisateur connecté peut consulter la liste. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Tout utilisateur connecté peut consulter cette équipe. */
    public function view(User $user, Team $team): bool
    {
        return true;
    }

    /** Création réservée aux administrateurs. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Modification de cette équipe réservée aux administrateurs. */
    public function update(User $user, Team $team): bool
    {
        return $user->isAdmin();
    }

    /** Suppression de cette équipe réservée aux administrateurs. */
    public function delete(User $user, Team $team): bool
    {
        return $user->isAdmin();
    }
}

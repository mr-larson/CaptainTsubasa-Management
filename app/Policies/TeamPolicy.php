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
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Team $team): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->isAdmin();
    }
}

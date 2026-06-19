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
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Player $player): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Player $player): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Player $player): bool
    {
        return $user->isAdmin();
    }
}

<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

/**
 * Données de référence (roster canonique) : consultation comme modification
 * sont réservées aux administrateurs, comme les routes qui y mènent (middleware
 * `admin`). Les joueurs ne voient ces données qu'à travers leurs parties.
 */
class TeamPolicy
{
    /** Consultation de la liste réservée aux administrateurs (écrans d'administration). */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Consultation de cette équipe réservée aux administrateurs. */
    public function view(User $user, Team $team): bool
    {
        return $user->isAdmin();
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

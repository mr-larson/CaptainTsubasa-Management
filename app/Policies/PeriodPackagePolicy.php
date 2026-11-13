<?php

namespace App\Policies;

use App\Models\PeriodPackage;
use App\Models\User;

/**
 * Un package de période appartient à son auteur. Privé par défaut : visible
 * par les autres uniquement si `is_shared`. Les administrateurs passent via
 * le Gate::before défini dans AuthServiceProvider.
 */
class PeriodPackagePolicy
{
    /** Tout utilisateur connecté peut lister les packages. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Package visible par son auteur, ou par tous s'il est partagé. */
    public function view(User $user, PeriodPackage $package): bool
    {
        return $package->is_shared || $this->owns($user, $package);
    }

    /** Tout utilisateur connecté peut créer un package. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Seul l'auteur peut modifier le package. */
    public function update(User $user, PeriodPackage $package): bool
    {
        return $this->owns($user, $package);
    }

    /** Seul l'auteur peut supprimer le package. */
    public function delete(User $user, PeriodPackage $package): bool
    {
        return $this->owns($user, $package);
    }

    /** Vrai si l'utilisateur est l'auteur du package. */
    private function owns(User $user, PeriodPackage $package): bool
    {
        return (int) $user->id === (int) $package->user_id;
    }
}

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
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PeriodPackage $package): bool
    {
        return $package->is_shared || $this->owns($user, $package);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PeriodPackage $package): bool
    {
        return $this->owns($user, $package);
    }

    public function delete(User $user, PeriodPackage $package): bool
    {
        return $this->owns($user, $package);
    }

    private function owns(User $user, PeriodPackage $package): bool
    {
        return (int) $user->id === (int) $package->user_id;
    }
}

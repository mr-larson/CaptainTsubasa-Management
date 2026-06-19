<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

/**
 * Données de référence (contrats du roster canonique). La consultation est
 * ouverte aux utilisateurs connectés ; toute modification est réservée aux
 * administrateurs.
 */
class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contract $contract): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->isAdmin();
    }
}

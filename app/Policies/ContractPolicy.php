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
    /** Tout utilisateur connecté peut consulter la liste. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Tout utilisateur connecté peut consulter ce contrat. */
    public function view(User $user, Contract $contract): bool
    {
        return true;
    }

    /** Création réservée aux administrateurs. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Modification de ce contrat réservée aux administrateurs. */
    public function update(User $user, Contract $contract): bool
    {
        return $user->isAdmin();
    }

    /** Suppression de ce contrat réservée aux administrateurs. */
    public function delete(User $user, Contract $contract): bool
    {
        return $user->isAdmin();
    }
}

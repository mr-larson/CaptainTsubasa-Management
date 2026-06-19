<?php

namespace App\Policies;

use App\Models\GameSaves\GameSave;
use App\Models\User;

/**
 * Une sauvegarde (et tout son contenu : équipes, joueurs, contrats, matchs…)
 * appartient à l'utilisateur qui l'a créée. Les administrateurs passent via
 * le Gate::before défini dans AuthServiceProvider.
 */
class GameSavePolicy
{
    public function viewAny(User $user): bool
    {
        // Chaque utilisateur ne voit que ses propres sauvegardes :
        // la liste est filtrée par user_id dans le contrôleur.
        return true;
    }

    public function view(User $user, GameSave $gameSave): bool
    {
        return $this->owns($user, $gameSave);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, GameSave $gameSave): bool
    {
        return $this->owns($user, $gameSave);
    }

    public function delete(User $user, GameSave $gameSave): bool
    {
        return $this->owns($user, $gameSave);
    }

    private function owns(User $user, GameSave $gameSave): bool
    {
        return (int) $user->id === (int) $gameSave->user_id;
    }
}

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
    /** Tout utilisateur connecté peut lister ses sauvegardes. */
    public function viewAny(User $user): bool
    {
        // Chaque utilisateur ne voit que ses propres sauvegardes :
        // la liste est filtrée par user_id dans le contrôleur.
        return true;
    }

    /** Seul le propriétaire peut consulter la sauvegarde. */
    public function view(User $user, GameSave $gameSave): bool
    {
        return $this->owns($user, $gameSave);
    }

    /** Tout utilisateur connecté peut créer une sauvegarde. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Seul le propriétaire peut modifier la sauvegarde. */
    public function update(User $user, GameSave $gameSave): bool
    {
        return $this->owns($user, $gameSave);
    }

    /** Seul le propriétaire peut supprimer la sauvegarde. */
    public function delete(User $user, GameSave $gameSave): bool
    {
        return $this->owns($user, $gameSave);
    }

    /** Vrai si l'utilisateur est le propriétaire de la sauvegarde. */
    private function owns(User $user, GameSave $gameSave): bool
    {
        return (int) $user->id === (int) $gameSave->user_id;
    }
}

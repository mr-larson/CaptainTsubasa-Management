<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Comptes par défaut : un administrateur et un utilisateur standard.
     *
     * Idempotent (updateOrCreate sur l'email). Le flag is_admin est positionné
     * explicitement car il est volontairement hors de $fillable (pas d'assignation
     * de masse possible depuis une requête).
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'gauthierdewit@gmail.com'],
            ['name' => 'Gauthier Dewit', 'password' => 'Adm!n2010'],
        );
        $admin->is_admin = true;
        $admin->save();

        $user = User::updateOrCreate(
            ['email' => 'user@gmail.com'],
            ['name' => 'user', 'password' => 'password'],
        );
        $user->is_admin = false;
        $user->save();
    }
}

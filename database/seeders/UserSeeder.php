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
            ['email' => 'admin@mail.com'],
            ['name' => 'admin', 'password' => 'Admin'],
        );
        $admin->is_admin = true;
        $admin->save();

        $user = User::updateOrCreate(
            ['email' => 'user@mail.com'],
            ['name' => 'user', 'password' => 'User'],
        );
        $user->is_admin = false;
        $user->save();
    }
}

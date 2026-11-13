<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;

/**
 * Point d'entrée de `php artisan db:seed` : remplit la base avec les données de
 * référence du jeu, dans l'ordre. Les comptes de test d'abord, puis le catalogue
 * (équipes, joueurs, duos d'alchimie), les contrats de référence et les cartes.
 * Ordre important : les contrats et les duos lisent les équipes et joueurs déjà semés.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TeamSeeder::class,
            PlayerSeeder::class,
            PlayerLinkSeeder::class,
            ContractSeeder::class,
            BonusCardSeeder::class,
        ]);
    }
}

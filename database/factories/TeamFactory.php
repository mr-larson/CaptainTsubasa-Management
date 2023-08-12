<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Team;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company, // Utilisez le générateur de noms d'entreprise pour un nom d'équipe
            'logo_path' => $this->faker->imageUrl(200, 200, 'sports'), // Génère une URL fictive pour une image
            'budget' => $this->faker->numberBetween(100000, 1000000), // Un budget aléatoire entre 100k et 1M
            'points' => $this->faker->numberBetween(0, 100), // Des points aléatoires entre 0 et 100
            'wins' => $this->faker->numberBetween(0, 50), // Victoires aléatoires entre 0 et 50
            'draws' => $this->faker->numberBetween(0, 25), // Matchs nuls aléatoires entre 0 et 25
            'losses' => $this->faker->numberBetween(0, 50), // Défaites aléatoires entre 0 et 50
            'team_stats_bonus' => json_encode(['someKey' => 'someValue', 'anotherKey' => 'anotherValue']),
            'active_cards' => json_encode(['card1', 'card2']),
        ];
    }
}

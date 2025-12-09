<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition()
    {
        $positions = ['Forward', 'Midfielder', 'Defender', 'Goalkeeper']; // Exemple de positions
        $skills = ['Speed', 'Strength', 'Technique']; // Exemple de compétences
        $moves = ['Dribble', 'Pass', 'Shot']; // Exemple de mouvements
        $weather = ['Rain', 'Sunny', 'Snow']; // Exemple de bonus météorologiques

        return [
            'name' => $this->faker->lastName,
            'first_name' => $this->faker->firstName,
            'image_path' => 'images/' . Str::random(10) . '.jpg', // Ceci est un chemin fictif
            'nationality' => $this->faker->country,
            'birth_date' => $this->faker->date(),
            'height' => $this->faker->numberBetween(150, 200), // Hauteur en cm
            'weight' => $this->faker->numberBetween(50, 100), // Poids en kg
            'period' => $this->faker->randomElement(['collège', 'lycée', 'pro']), // Périodes fictives
            'current_team_id' => function() {
                return Team::all()->random()->id;
            },
            'stats' => [
                'goals' => $this->faker->numberBetween(0, 50),
                'assists' => $this->faker->numberBetween(0, 30)
                // Ajoutez d'autres statistiques au besoin
            ],
            'positions' => $this->faker->randomElements($positions, $this->faker->numberBetween(1, count($positions))),
            'special_skills' => $this->faker->randomElements($skills, $this->faker->numberBetween(1, count($skills))),
            'special_moves' => $this->faker->randomElements($moves, $this->faker->numberBetween(1, count($moves))),
            'weather_bonus' => $this->faker->randomElements($weather, $this->faker->numberBetween(1, count($weather))),
            'cost' => $this->faker->numberBetween(1000, 100000),
            'current_contract_duration' => $this->faker->numberBetween(1, 5),
            'fatigue' => $this->faker->numberBetween(0, 100),
            'injury_risk' => $this->faker->randomFloat(2, 0, 100),
            'is_injured' => $this->faker->boolean,
        ];
    }
}


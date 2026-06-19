<?php

namespace Database\Factories;

use App\Enums\PlayerPosition;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition()
    {
        return [
            'firstname' => $this->faker->firstName,
            'lastname'  => $this->faker->lastName,
            'age'       => $this->faker->numberBetween(15, 35),
            'position'  => $this->faker->randomElement(PlayerPosition::values()),
            'secondary_positions' => [],
            'cost'      => $this->faker->numberBetween(1000, 100000),
            'stats'     => [
                'speed'   => $this->faker->numberBetween(40, 99),
                'stamina' => $this->faker->numberBetween(40, 99),
                'attack'  => $this->faker->numberBetween(40, 99),
                'defense' => $this->faker->numberBetween(40, 99),
                'shot'    => $this->faker->numberBetween(40, 99),
                'pass'    => $this->faker->numberBetween(40, 99),
                'dribble' => $this->faker->numberBetween(40, 99),
            ],
            'special_moves' => [],
            'description'   => $this->faker->sentence(),
            'photo_path'    => null,
        ];
    }
}


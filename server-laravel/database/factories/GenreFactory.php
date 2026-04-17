<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GenreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'glpi_id' => fake()->unique()->randomNumber(5),
        ];
    }
}

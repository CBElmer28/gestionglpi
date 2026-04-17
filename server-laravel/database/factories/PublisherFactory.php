<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PublisherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'glpi_id' => fake()->unique()->randomNumber(5),
        ];
    }
}

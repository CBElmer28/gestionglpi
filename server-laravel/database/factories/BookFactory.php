<?php

namespace Database\Factories;

use App\Models\Genre;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'isbn' => fake()->isbn13(),
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'genre_id' => Genre::factory(),
            'publisher_id' => Publisher::factory(),
            'status' => 'Disponible',
            'glpi_id' => fake()->randomNumber(4),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Genre>
 */
class GenreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $genres = [
            'Fantasy', 'Science Fiction', 'Mystery', 'Thriller', 'Romance',
            'Historical Fiction', 'Horror', 'Literary Fiction', 'Non-Fiction', 'Young Adult'
        ];

        return [
            'name' => fake()->unique()->randomElement($genres),
            'bg_color' => fake()->hexColor(),
            'text_color' => fake()->hexColor(),
        ];
    }
}

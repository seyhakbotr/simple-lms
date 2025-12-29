<?php

namespace Database\Factories;

use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Author>
 */
class AuthorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $authors = [
            'Stephen King', 'J.K. Rowling', 'George R.R. Martin', 'Haruki Murakami', 'Margaret Atwood',
            'Neil Gaiman', 'Chimamanda Ngozi Adichie', 'Kazuo Ishiguro', 'Zadie Smith', 'David Mitchell'
        ];

        return [
            'name' => fake()->unique()->randomElement($authors),
            'date_of_birth' => fake()->dateTimeThisCentury(),
            'bio' => fake()->realText(200),
        ];
    }
}

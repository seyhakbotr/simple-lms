<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Publisher>
 */
class PublisherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $publishers = [
            'Penguin Random House', 'Hachette Livre', 'HarperCollins', 'Macmillan Publishers', 'Simon & Schuster',
            'McGraw-Hill Education', 'Scholastic Corporation', 'Pearson Education', 'Oxford University Press', 'Cambridge University Press'
        ];

        return [
            'name' => fake()->unique()->randomElement($publishers),
            'founded' => fake()->dateTimeThisCentury(),
        ];
    }
}

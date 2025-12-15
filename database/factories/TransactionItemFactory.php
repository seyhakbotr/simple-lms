<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'book_id' => Book::factory(),
            'borrowed_for' => fake()->numberBetween(7, 30),
            'fine' => null,
        ];
    }

    /**
     * Indicate that this item has a fine (was returned late).
     */
    public function withFine(int $fine = null): static
    {
        return $this->state(fn (array $attributes) => [
            'fine' => $fine ?? fake()->numberBetween(10, 100),
        ]);
    }

    /**
     * Set a specific borrowed duration.
     */
    public function borrowedFor(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'borrowed_for' => $days,
        ]);
    }
}

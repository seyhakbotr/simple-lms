<?php

namespace Database\Factories;

use App\Enums\BorrowedStatus;
use App\Models\Book;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $borrowedDate = fake()->dateTimeBetween("-30 days", "now");
        $hasReturned = fake()->boolean(70); // 70% chance of being returned

        return [
            "user_id" => User::factory(),
            "borrowed_date" => $borrowedDate,
            "returned_date" => $hasReturned
                ? fake()->dateTimeBetween($borrowedDate, "now")
                : null,
            "status" => $hasReturned
                ? fake()->randomElement([
                    BorrowedStatus::Returned,
                    BorrowedStatus::Delayed,
                ])
                : BorrowedStatus::Borrowed,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($transaction) {
            // Create 1-3 transaction items (books) for each transaction
            $itemCount = fake()->numberBetween(1, 3);

            // Get random books
            $books = Book::inRandomOrder()->limit($itemCount)->get();

            foreach ($books as $book) {
                $borrowedFor = fake()->numberBetween(7, 30);

                TransactionItem::create([
                    "transaction_id" => $transaction->id,
                    "book_id" => $book->id,
                    "borrowed_for" => $borrowedFor,
                ]);
            }

            // Calculate fines for all items after creation if transaction is returned
            if ($transaction->returned_date) {
                $transaction->refresh();
                $feeCalculator = app(\App\Services\FeeCalculator::class);

                foreach ($transaction->items as $item) {
                    // Calculate overdue fine using the FeeCalculator
                    $overdueFine = $feeCalculator->calculateOverdueFine(
                        $item,
                        $transaction->returned_date,
                    );

                    if ($overdueFine > 0) {
                        $item->update([
                            "overdue_fine" => $overdueFine,
                            "total_fine" => $overdueFine,
                            "fine" => $overdueFine, // Legacy field
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Indicate that the transaction is currently borrowed (not returned).
     */
    public function borrowed(): static
    {
        return $this->state(
            fn(array $attributes) => [
                "returned_date" => null,
                "status" => BorrowedStatus::Borrowed,
            ],
        );
    }

    /**
     * Indicate that the transaction has been returned on time.
     */
    public function returned(): static
    {
        return $this->state(function (array $attributes) {
            $borrowedDate = Carbon::parse($attributes["borrowed_date"]);

            return [
                "returned_date" => $borrowedDate
                    ->copy()
                    ->addDays(fake()->numberBetween(1, 14)),
                "status" => BorrowedStatus::Returned,
            ];
        });
    }

    /**
     * Indicate that the transaction has been returned late (with fine).
     */
    public function delayed(): static
    {
        return $this->state(function (array $attributes) {
            $borrowedDate = Carbon::parse($attributes["borrowed_date"]);

            return [
                "returned_date" => $borrowedDate
                    ->copy()
                    ->addDays(fake()->numberBetween(15, 45)),
                "status" => BorrowedStatus::Delayed,
            ];
        });
    }
}

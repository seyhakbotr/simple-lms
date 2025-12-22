<?php

use App\Models\Book;
use App\Models\MembershipType;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\FeeCalculator;
use App\Settings\FeeSettings;
use Illuminate\Support\Carbon;
use Spatie\LaravelSettings\Facades\Settings;

describe("FeeCalculator overdue fine calculation", function () {
    beforeEach(function () {
        // Set fee settings with grace period
        Settings::set("fees", [
            "overdue_fee_enabled" => true,
            "overdue_fee_per_day" => 10.0, // $10 per day
            "grace_period_days" => 2, // 2 day grace period
            "overdue_fee_max_days" => null,
            "overdue_fee_max_amount" => null,
            "waive_small_amounts" => false,
            "small_amount_threshold" => 1.0,
            "currency_symbol" => '$',
            "currency_code" => "USD",
            "lost_book_fine_rate" => 100.0,
            "lost_book_fine_type" => "percentage",
            "lost_book_minimum_fine" => null,
            "lost_book_maximum_fine" => null,
            "allow_partial_payment" => true,
            "send_overdue_notifications" => true,
            "overdue_notification_days" => 3,
        ]);

        $this->feeCalculator = app(FeeCalculator::class);
    });

    test(
        "calculates overdue fine factoring membership loan period + grace period",
        function () {
            // Create a membership type with 14 day loan period
            $membershipType = MembershipType::factory()->create([
                "name" => "Standard",
                "max_borrow_days" => 14,
            ]);

            // Create a user with this membership
            $user = User::factory()->create([
                "membership_type_id" => $membershipType->id,
            ]);

            // Create a book
            $book = Book::factory()->create([
                "title" => "Test Book",
                "available" => true,
            ]);

            // Create a transaction borrowed 20 days ago (loan period is 14 days)
            $borrowedDate = Carbon::now()->subDays(20);
            $transaction = Transaction::factory()->create([
                "user_id" => $user->id,
                "borrowed_date" => $borrowedDate,
                "due_date" => $borrowedDate->copy()->addDays(14), // Due after 14 days (loan period)
                "returned_date" => null,
            ]);

            // Create transaction item
            $item = TransactionItem::factory()->create([
                "transaction_id" => $transaction->id,
                "book_id" => $book->id,
                "borrowed_for" => 14,
            ]);

            // Return the book today (20 days after borrow)
            $returnDate = Carbon::now();
            $transaction->update(["returned_date" => $returnDate]);

            // Calculate overdue fine
            $fine = $this->feeCalculator->calculateOverdueFine(
                $item,
                $returnDate,
            );

            // Expected calculation:
            // Due date: borrowed + 14 days = 6 days ago
            // Return date: today
            // Days late: 6 days
            // After grace period (2 days): 6 - 2 = 4 days
            // Fine: 4 days * $10/day = $40
            expect($fine)->toBe(40.0);
        },
    );

    test("no fine charged within loan period + grace period", function () {
        // Create a membership type with 7 day loan period
        $membershipType = MembershipType::factory()->create([
            "name" => "Short Term",
            "max_borrow_days" => 7,
        ]);

        $user = User::factory()->create([
            "membership_type_id" => $membershipType->id,
        ]);

        $book = Book::factory()->create();

        // Borrowed 8 days ago, due in 7 days, so due 1 day ago
        $borrowedDate = Carbon::now()->subDays(8);
        $transaction = Transaction::factory()->create([
            "user_id" => $user->id,
            "borrowed_date" => $borrowedDate,
            "due_date" => $borrowedDate->copy()->addDays(7),
            "returned_date" => null,
        ]);

        $item = TransactionItem::factory()->create([
            "transaction_id" => $transaction->id,
            "book_id" => $book->id,
            "borrowed_for" => 7,
        ]);

        // Return today (1 day late)
        $returnDate = Carbon::now();
        $transaction->update(["returned_date" => $returnDate]);

        $fine = $this->feeCalculator->calculateOverdueFine($item, $returnDate);

        // Days late: 1
        // After grace period: 1 - 2 = -1 (no fine)
        expect($fine)->toBe(0.0);
    });

    test("fine charged after loan period + grace period exceeded", function () {
        $membershipType = MembershipType::factory()->create([
            "max_borrow_days" => 10,
        ]);

        $user = User::factory()->create([
            "membership_type_id" => $membershipType->id,
        ]);

        $book = Book::factory()->create();

        // Borrowed 15 days ago, due 10 days ago
        $borrowedDate = Carbon::now()->subDays(15);
        $transaction = Transaction::factory()->create([
            "user_id" => $user->id,
            "borrowed_date" => $borrowedDate,
            "due_date" => $borrowedDate->copy()->addDays(10),
        ]);

        $item = TransactionItem::factory()->create([
            "transaction_id" => $transaction->id,
            "book_id" => $book->id,
            "borrowed_for" => 10,
        ]);

        // Return today (15 days after borrow, 5 days after due)
        $returnDate = Carbon::now();
        $transaction->update(["returned_date" => $returnDate]);

        $fine = $this->feeCalculator->calculateOverdueFine($item, $returnDate);

        // Days late: 5
        // After grace period: 5 - 2 = 3 days
        // Fine: 3 * $15 = $45
        expect($fine)->toBe(45.0);
    });
});

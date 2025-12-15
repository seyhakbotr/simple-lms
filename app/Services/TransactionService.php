<?php

namespace App\Services;

use App\Enums\BorrowedStatus;
use App\Models\Book;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    protected FeeCalculator $feeCalculator;

    public function __construct(FeeCalculator $feeCalculator)
    {
        $this->feeCalculator = $feeCalculator;
    }

    /**
     * Validate if a user can borrow books
     *
     * @param User $user
     * @param int $booksCount Number of books to borrow
     * @return array ['can_borrow' => bool, 'message' => string, 'details' => array]
     */
    public function validateBorrowingCapacity(
        User $user,
        int $booksCount
    ): array {
        if (!$user->membershipType) {
            return [
                "can_borrow" => false,
                "message" =>
                    "User does not have a membership type assigned.",
                "details" => [
                    "current_count" => 0,
                    "max_allowed" => 0,
                    "requesting" => $booksCount,
                ],
            ];
        }

        $currentBorrowed = $user->getCurrentBorrowedBooksCount();
        $maxAllowed = $user->membershipType->max_books_allowed;
        $totalAfter = $currentBorrowed + $booksCount;

        if ($totalAfter > $maxAllowed) {
            return [
                "can_borrow" => false,
                "message" =>
                    "User has {$currentBorrowed} book(s) borrowed. Their membership type ({$user->membershipType->name}) allows maximum {$maxAllowed} book(s). Cannot borrow {$booksCount} more book(s).",
                "details" => [
                    "current_count" => $currentBorrowed,
                    "max_allowed" => $maxAllowed,
                    "requesting" => $booksCount,
                    "total_after" => $totalAfter,
                    "membership_type" => $user->membershipType->name,
                ],
            ];
        }

        return [
            "can_borrow" => true,
            "message" => "User can borrow {$booksCount} book(s).",
            "details" => [
                "current_count" => $currentBorrowed,
                "max_allowed" => $maxAllowed,
                "requesting" => $booksCount,
                "total_after" => $totalAfter,
                "remaining_after" => $maxAllowed - $totalAfter,
                "membership_type" => $user->membershipType->name,
            ],
        ];
    }

    /**
     * Validate borrow duration against membership type limits
     *
     * @param User $user
     * @param int $borrowDays
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateBorrowDuration(
        User $user,
        int $borrowDays
    ): array {
        if (!$user->membershipType) {
            return [
                "valid" => false,
                "message" => "User does not have a membership type assigned.",
                "max_days" => 0,
            ];
        }

        $maxDays = $user->membershipType->max_borrow_days;

        if ($borrowDays > $maxDays) {
            return [
                "valid" => false,
                "message" =>
                    "Borrow duration ({$borrowDays} days) exceeds the maximum of {$maxDays} days allowed for {$user->membershipType->name} membership.",
                "max_days" => $maxDays,
                "requested_days" => $borrowDays,
            ];
        }

        return [
            "valid" => true,
            "message" => "Borrow duration is valid.",
            "max_days" => $maxDays,
            "requested_days" => $borrowDays,
        ];
    }

    /**
     * Create a new transaction with items
     *
     * @param array $data ['user_id' => int, 'borrowed_date' => Carbon|string, 'items' => array]
     * @return Transaction
     * @throws ValidationException
     */
    public function createTransaction(array $data): Transaction
    {
        $user = User::with("membershipType")->find($data["user_id"]);

        if (!$user) {
            throw ValidationException::withMessages([
                "user_id" => "The selected user does not exist.",
            ]);
        }

        // Validate borrowing capacity
        $itemsCount = isset($data["items"]) ? count($data["items"]) : 0;
        $validation = $this->validateBorrowingCapacity($user, $itemsCount);

        if (!$validation["can_borrow"]) {
            throw ValidationException::withMessages([
                "items" => $validation["message"],
            ]);
        }

        // Validate each item's borrow duration
        if (isset($data["items"])) {
            foreach ($data["items"] as $index => $item) {
                $durationValidation = $this->validateBorrowDuration(
                    $user,
                    $item["borrowed_for"] ?? 0,
                );

                if (!$durationValidation["valid"]) {
                    throw ValidationException::withMessages([
                        "items.{$index}.borrowed_for" =>
                            $durationValidation["message"],
                    ]);
                }
            }
        }

        // Create transaction in a database transaction
        return DB::transaction(function () use ($data, $user) {
            // Calculate due date
            $borrowedDate = Carbon::parse($data["borrowed_date"]);
            $maxBorrowDays =
                $user->membershipType->max_borrow_days ??
                14;

            // Create the transaction
            $transaction = Transaction::create([
                "user_id" => $data["user_id"],
                "borrowed_date" => $borrowedDate,
                "due_date" =>
                    $data["due_date"] ??
                    $borrowedDate->copy()->addDays($maxBorrowDays),
                "status" => $data["status"] ?? BorrowedStatus::Borrowed,
                "returned_date" => $data["returned_date"] ?? null,
                "renewed_count" => 0,
            ]);

            // Create transaction items
            if (isset($data["items"])) {
                foreach ($data["items"] as $itemData) {
                    TransactionItem::create([
                        "transaction_id" => $transaction->id,
                        "book_id" => $itemData["book_id"],
                        "borrowed_for" => $itemData["borrowed_for"],
                        "fine" => 0, // Will be calculated on return
                    ]);

                    // Update book availability
                    $book = Book::find($itemData["book_id"]);
                    if ($book && $book->stock > 0) {
                        $book->decrement("stock");
                    }
                }
            }

            return $transaction->fresh(["items.book", "user.membershipType"]);
        });
    }

    /**
     * Return a transaction and calculate fines
     *
     * @param Transaction $transaction
     * @param Carbon|string|null $returnDate
     * @return Transaction
     */
    public function returnTransaction(
        Transaction $transaction,
        $returnDate = null
    ): Transaction {
        $returnDate = $returnDate ? Carbon::parse($returnDate) : now();

        return DB::transaction(function () use ($transaction, $returnDate) {
            // Update transaction
            $transaction->update([
                "returned_date" => $returnDate,
                "status" => $transaction->isOverdue()
                    ? BorrowedStatus::Delayed
                    : BorrowedStatus::Returned,
            ]);

            // Calculate and store fines for each item
            foreach ($transaction->items as $item) {
                $fine = $this->feeCalculator->calculateOverdueFine(
                    $item,
                    $returnDate,
                );
                $item->update(["fine" => $fine]);

                // Restore book stock
                $book = $item->book;
                if ($book) {
                    $book->increment("stock");
                }
            }

            return $transaction->fresh(["items.book", "user.membershipType"]);
        });
    }

    /**
     * Renew a transaction
     *
     * @param Transaction $transaction
     * @return array ['success' => bool, 'message' => string, 'transaction' => Transaction|null]
     */
    public function renewTransaction(Transaction $transaction): array
    {
        if (!$transaction->canRenew()) {
            $reasons = [];

            if ($transaction->returned_date) {
                $reasons[] = "Transaction has already been returned";
            }

            if ($transaction->isOverdue()) {
                $reasons[] =
                    "Transaction is overdue by " .
                    $transaction->getDaysOverdue() .
                    " day(s)";
            }

            $maxRenewals =
                $transaction->user->membershipType?->renewal_limit ?? 0;
            if ($transaction->renewed_count >= $maxRenewals) {
                $reasons[] =
                    "Maximum renewals reached ({$transaction->renewed_count}/{$maxRenewals})";
            }

            return [
                "success" => false,
                "message" => "Cannot renew transaction: " . implode(", ", $reasons),
                "transaction" => null,
                "reasons" => $reasons,
            ];
        }

        $renewalDays =
            $transaction->user->membershipType?->max_borrow_days ?? 14;

        $transaction->update([
            "due_date" => $transaction->due_date->addDays($renewalDays),
            "renewed_count" => $transaction->renewed_count + 1,
        ]);

        return [
            "success" => true,
            "message" => "Transaction renewed successfully. New due date: " .
                $transaction->due_date->format("Y-m-d"),
            "transaction" => $transaction->fresh([
                "items.book",
                "user.membershipType",
            ]),
            "renewed_count" => $transaction->renewed_count,
            "new_due_date" => $transaction->due_date,
            "days_added" => $renewalDays,
        ];
    }

    /**
     * Get current overdue fine preview for an active transaction
     *
     * @param Transaction $transaction
     * @return array ['total' => int, 'formatted' => string, 'items' => array]
     */
    public function getCurrentOverdueFine(Transaction $transaction): array
    {
        if ($transaction->returned_date) {
            return [
                "total" => $transaction->total_fine,
                "formatted" => $transaction->formatted_total_fine,
                "items" => $transaction->items
                    ->map(function ($item) {
                        return [
                            "book_title" => $item->book->title,
                            "fine" => $item->fine,
                            "formatted" => $item->formatted_fine,
                        ];
                    })
                    ->toArray(),
            ];
        }

        $breakdown = $this->feeCalculator->getTransactionFeeBreakdown(
            $transaction,
        );

        return [
            "total" => $breakdown["total"],
            "formatted" => $breakdown["formatted_total"],
            "items" => $breakdown["items"],
            "is_preview" => true,
            "days_overdue" => $transaction->getDaysOverdue(),
        ];
    }

    /**
     * Get transaction summary with all relevant information
     *
     * @param Transaction $transaction
     * @return array
     */
    public function getTransactionSummary(Transaction $transaction): array
    {
        $summary = [
            "id" => $transaction->id,
            "user" => [
                "id" => $transaction->user->id,
                "name" => $transaction->user->name,
                "email" => $transaction->user->email,
                "membership_type" =>
                    $transaction->user->membershipType?->name ?? "None",
            ],
            "dates" => [
                "borrowed" => $transaction->borrowed_date->format("Y-m-d"),
                "due" => $transaction->due_date->format("Y-m-d"),
                "returned" => $transaction->returned_date
                    ? $transaction->returned_date->format("Y-m-d")
                    : null,
            ],
            "status" => [
                "value" => $transaction->status->value,
                "label" => $transaction->status->getLabel(),
                "is_overdue" => $transaction->isOverdue(),
                "days_overdue" => $transaction->getDaysOverdue(),
            ],
            "renewal" => [
                "count" => $transaction->renewed_count,
                "max_allowed" =>
                    $transaction->user->membershipType?->renewal_limit ?? 0,
                "can_renew" => $transaction->canRenew(),
            ],
            "items" => $transaction->items
                ->map(function ($item) {
                    return [
                        "id" => $item->id,
                        "book_id" => $item->book_id,
                        "book_title" => $item->book->title,
                        "borrowed_for" => $item->borrowed_for,
                        "due_date" => $item->due_date->format("Y-m-d"),
                        "fine" => $item->fine ?? 0,
                        "formatted_fine" => $item->formatted_fine,
                    ];
                })
                ->toArray(),
            "fines" => $this->getCurrentOverdueFine($transaction),
        ];

        return $summary;
    }

    /**
     * Get user's active transactions summary
     *
     * @param User $user
     * @return array
     */
    public function getUserActiveTransactionsSummary(User $user): array
    {
        $activeTransactions = $user
            ->activeTransactions()
            ->with(["items.book"])
            ->get();

        $totalBooksOut = $activeTransactions->sum(function ($transaction) {
            return $transaction->items->count();
        });

        $maxAllowed = $user->membershipType?->max_books_allowed ?? 0;

        return [
            "user_id" => $user->id,
            "user_name" => $user->name,
            "membership_type" => $user->membershipType?->name ?? "None",
            "total_books_borrowed" => $totalBooksOut,
            "max_books_allowed" => $maxAllowed,
            "remaining_capacity" => max(0, $maxAllowed - $totalBooksOut),
            "can_borrow_more" => $totalBooksOut < $maxAllowed,
            "active_transactions_count" => $activeTransactions->count(),
            "has_overdue" => $activeTransactions->some(
                fn($t) => $t->isOverdue(),
            ),
            "total_current_fines" => $activeTransactions->sum(
                fn($t) => $t->total_fine,
            ),
        ];
    }
}

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
    public function __construct(protected FeeCalculator $feeCalculator) {}

    /**
     * Create a new borrow transaction
     *
     * @param array $data
     * @return Transaction
     * @throws ValidationException
     */
    public function createBorrowTransaction(array $data): Transaction
    {
        $user = User::with("membershipType")->findOrFail($data["user_id"]);

        // Validate membership
        $this->validateMembership($user);

        // Validate borrowing capacity
        $this->validateBorrowingCapacity($user, count($data["books"]));

        // Validate borrow duration
        $borrowDays =
            $data["borrow_days"] ?? $user->membershipType->max_borrow_days;
        $this->validateBorrowDuration($user, $borrowDays);

        // Validate book availability
        $this->validateBookAvailability($data["books"]);

        return DB::transaction(function () use ($data, $user, $borrowDays) {
            $borrowedDate = isset($data["borrowed_date"])
                ? Carbon::parse($data["borrowed_date"])
                : now();

            $dueDate = $borrowedDate->copy()->addDays($borrowDays);

            // Create transaction
            $transaction = Transaction::create([
                "user_id" => $user->id,
                "borrowed_date" => $borrowedDate,
                "due_date" => $dueDate,
                "status" => BorrowedStatus::Borrowed,
                "renewed_count" => 0,
            ]);

            // Add books to transaction
            foreach ($data["books"] as $bookId) {
                $book = Book::findOrFail($bookId);

                // Create transaction item
                TransactionItem::create([
                    "transaction_id" => $transaction->id,
                    "book_id" => $book->id,
                    "borrowed_for" => $borrowDays,
                ]);

                // Decrease book stock
                $book->decrement("stock");
            }

            return $transaction->fresh(["items.book", "user.membershipType"]);
        });
    }

    /**
     * Process return of a transaction
     *
     * @param Transaction $transaction
     * @param array $data
     * @return Transaction
     * @throws ValidationException
     */
    public function returnTransaction(
        Transaction $transaction,
        array $data = [],
    ): Transaction {
        if ($transaction->returned_date) {
            throw ValidationException::withMessages([
                "transaction" => "This transaction has already been returned.",
            ]);
        }

        $returnDate = isset($data["returned_date"])
            ? Carbon::parse($data["returned_date"])
            : now();

        return DB::transaction(function () use (
            $transaction,
            $returnDate,
            $data,
        ) {
            // Process each item
            foreach ($transaction->items as $item) {
                // Initialize fees
                $overdueFine = 0;
                $lostFine = 0;
                $damageFine = 0;
                $itemStatus = $item->item_status ?? "borrowed";

                \Log::info("=== Processing Item #{$item->id} ===");
                \Log::info("Book: {$item->book->title}");
                \Log::info("Return date: {$returnDate}");
                \Log::info("Due date: {$item->due_date}");

                // Calculate overdue fine first
                $overdueFine = $this->feeCalculator->calculateOverdueFine(
                    $item,
                    $returnDate,
                );
                \Log::info("Calculated overdue fine: \${$overdueFine}");

                // Handle lost items
                if (
                    isset($data["lost_items"]) &&
                    in_array($item->id, $data["lost_items"])
                ) {
                    $itemStatus = "lost";
                    $lostFine = $this->feeCalculator->calculateLostBookFine(
                        $item->book,
                    );
                    \Log::info("Item marked as LOST. Lost fine: \${$lostFine}");
                }

                // Handle damaged items
                if (isset($data["damaged_items"][$item->id])) {
                    $damageData = $data["damaged_items"][$item->id];
                    $itemStatus = "damaged";
                    $damageFine = $damageData["fine"] ?? 0;
                }

                // Calculate total fine
                $totalFine = $overdueFine + $lostFine + $damageFine;
                \Log::info("Total fine calculated: \${$totalFine}");

                // Update all fields at once
                \Log::info(
                    "Updating item with: overdue=\${$overdueFine}, lost=\${$lostFine}, damage=\${$damageFine}, total=\${$totalFine}",
                );
                $item->update([
                    "item_status" => $itemStatus,
                    "overdue_fine" => $overdueFine,
                    "lost_fine" => $lostFine,
                    "damage_fine" => $damageFine,
                    "damage_notes" => isset($data["damaged_items"][$item->id])
                        ? $data["damaged_items"][$item->id]["notes"] ?? null
                        : null,
                    "total_fine" => $totalFine,
                    "fine" => $totalFine, // Legacy field
                ]);

                // Verify what was saved
                $item->refresh();
                \Log::info("After save - DB values:");
                \Log::info(
                    "  overdue_fine in DB: " .
                        \DB::table("transaction_items")
                            ->where("id", $item->id)
                            ->value("overdue_fine") .
                        " cents",
                );
                \Log::info(
                    "  total_fine in DB: " .
                        \DB::table("transaction_items")
                            ->where("id", $item->id)
                            ->value("total_fine") .
                        " cents",
                );
                \Log::info("  overdue_fine via model: \${$item->overdue_fine}");
                \Log::info("  total_fine via model: \${$item->total_fine}");

                // Return book to stock (unless lost)
                if ($itemStatus !== "lost") {
                    $item->book->increment("stock");
                }
            }

            // Determine final transaction status
            $status = $this->determineReturnStatus($transaction, $returnDate);

            $transaction->update([
                "returned_date" => $returnDate,
                "status" => $status,
            ]);

            return $transaction->fresh(["items.book", "user.membershipType"]);
        });
    }

    /**
     * Renew a transaction
     *
     * @param Transaction $transaction
     * @return array
     */
    public function renewTransaction(Transaction $transaction): array
    {
        // Validate renewal eligibility
        $validation = $this->validateRenewal($transaction);

        if (!$validation["can_renew"]) {
            return [
                "success" => false,
                "message" => $validation["message"],
                "reasons" => $validation["reasons"],
            ];
        }

        $renewalDays = $transaction->user->membershipType->max_borrow_days;
        $newDueDate = $transaction->due_date->copy()->addDays($renewalDays);

        $transaction->update([
            "due_date" => $newDueDate,
            "renewed_count" => $transaction->renewed_count + 1,
        ]);

        return [
            "success" => true,
            "message" => "Transaction renewed successfully. New due date: {$newDueDate->format(
                "M d, Y",
            )}",
            "transaction" => $transaction->fresh([
                "items.book",
                "user.membershipType",
            ]),
            "new_due_date" => $newDueDate,
            "renewed_count" => $transaction->renewed_count,
            "days_added" => $renewalDays,
        ];
    }

    /**
     * Get preview of fees if transaction were returned today
     *
     * @param Transaction $transaction
     * @return array
     */
    public function previewReturnFees(Transaction $transaction): array
    {
        if ($transaction->returned_date) {
            return $this->getActualFees($transaction);
        }

        $preview = [
            "items" => [],
            "total_overdue" => 0,
            "total_all_fees" => 0,
            "is_preview" => true,
            "days_overdue" => $transaction->getDaysOverdue(),
            "currency_symbol" => $this->feeCalculator->getFeeSettings()
                ->currency_symbol,
        ];

        foreach ($transaction->items as $item) {
            $overdueFine = $this->feeCalculator->calculateCurrentOverdueFine(
                $item,
            );
            $lostFine = $item->lost_fine ?? 0;
            $damageFine = $item->damage_fine ?? 0;
            $totalFine = $overdueFine + $lostFine + $damageFine;

            $preview["items"][] = [
                "item_id" => $item->id,
                "book_title" => $item->book->title,
                "book_id" => $item->book->id,
                "overdue_fine" => $overdueFine,
                "lost_fine" => $lostFine,
                "damage_fine" => $damageFine,
                "total_fine" => $totalFine,
                "formatted_overdue" => $this->feeCalculator->formatFine(
                    $overdueFine,
                ),
                "formatted_lost" => $this->feeCalculator->formatFine($lostFine),
                "formatted_damage" => $this->feeCalculator->formatFine(
                    $damageFine,
                ),
                "formatted_total" => $this->feeCalculator->formatFine(
                    $totalFine,
                ),
                "item_status" => $item->item_status,
            ];

            $preview["total_overdue"] += $overdueFine;
            $preview["total_all_fees"] += $totalFine;
        }

        $preview["formatted_total_overdue"] = $this->feeCalculator->formatFine(
            $preview["total_overdue"],
        );
        $preview["formatted_total_all"] = $this->feeCalculator->formatFine(
            $preview["total_all_fees"],
        );

        return $preview;
    }

    /**
     * Get actual fees for a returned transaction
     *
     * @param Transaction $transaction
     * @return array
     */
    public function getActualFees(Transaction $transaction): array
    {
        $fees = [
            "items" => [],
            "total_overdue" => 0,
            "total_lost" => 0,
            "total_damage" => 0,
            "total_all_fees" => 0,
            "is_preview" => false,
            "currency_symbol" => $this->feeCalculator->getFeeSettings()
                ->currency_symbol,
        ];

        foreach ($transaction->items as $item) {
            $itemFees = [
                "item_id" => $item->id,
                "book_title" => $item->book->title,
                "book_id" => $item->book->id,
                "overdue_fine" => $item->overdue_fine ?? 0,
                "lost_fine" => $item->lost_fine ?? 0,
                "damage_fine" => $item->damage_fine ?? 0,
                "total_fine" => $item->total_fine ?? 0,
                "formatted_overdue" => $this->feeCalculator->formatFine(
                    $item->overdue_fine ?? 0,
                ),
                "formatted_lost" => $this->feeCalculator->formatFine(
                    $item->lost_fine ?? 0,
                ),
                "formatted_damage" => $this->feeCalculator->formatFine(
                    $item->damage_fine ?? 0,
                ),
                "formatted_total" => $this->feeCalculator->formatFine(
                    $item->total_fine ?? 0,
                ),
                "item_status" => $item->item_status,
                "damage_notes" => $item->damage_notes,
            ];

            $fees["items"][] = $itemFees;
            $fees["total_overdue"] += $item->overdue_fine ?? 0;
            $fees["total_lost"] += $item->lost_fine ?? 0;
            $fees["total_damage"] += $item->damage_fine ?? 0;
            $fees["total_all_fees"] += $item->total_fine ?? 0;
        }

        $fees["formatted_total_overdue"] = $this->feeCalculator->formatFine(
            $fees["total_overdue"],
        );
        $fees["formatted_total_lost"] = $this->feeCalculator->formatFine(
            $fees["total_lost"],
        );
        $fees["formatted_total_damage"] = $this->feeCalculator->formatFine(
            $fees["total_damage"],
        );
        $fees["formatted_total_all"] = $this->feeCalculator->formatFine(
            $fees["total_all_fees"],
        );

        return $fees;
    }

    /**
     * Get user's borrowing summary
     *
     * @param User $user
     * @return array
     */
    public function getUserBorrowingSummary(User $user): array
    {
        $activeTransactions = $user
            ->activeTransactions()
            ->with("items.book")
            ->get();
        $totalBooksOut = $activeTransactions->sum(fn($t) => $t->items->count());
        $maxAllowed = $user->membershipType?->max_books_allowed ?? 0;

        return [
            "user_id" => $user->id,
            "user_name" => $user->name,
            "membership_type" => $user->membershipType?->name ?? "None",
            "membership_active" => $user->hasActiveMembership(),
            "membership_expires" => $user->membership_expires_at?->format(
                "M d, Y",
            ),
            "total_books_borrowed" => $totalBooksOut,
            "max_books_allowed" => $maxAllowed,
            "available_slots" => max(0, $maxAllowed - $totalBooksOut),
            "can_borrow_more" =>
                $totalBooksOut < $maxAllowed && $user->hasActiveMembership(),
            "active_transactions_count" => $activeTransactions->count(),
            "has_overdue" => $activeTransactions->contains(
                fn($t) => $t->isOverdue(),
            ),
            "overdue_count" => $activeTransactions
                ->filter(fn($t) => $t->isOverdue())
                ->count(),
        ];
    }

    /**
     * Validate user's membership
     *
     * @param User $user
     * @throws ValidationException
     */
    protected function validateMembership(User $user): void
    {
        if (!$user->membershipType) {
            throw ValidationException::withMessages([
                "user_id" => "User does not have a membership type assigned.",
            ]);
        }

        if (!$user->hasActiveMembership()) {
            $expiry = $user->membership_expires_at?->format("M d, Y") ?? "N/A";
            throw ValidationException::withMessages([
                "user_id" => "User's membership has expired on {$expiry}. Please renew membership first.",
            ]);
        }
    }

    /**
     * Validate borrowing capacity
     *
     * @param User $user
     * @param int $requestedBooks
     * @throws ValidationException
     */
    protected function validateBorrowingCapacity(
        User $user,
        int $requestedBooks,
    ): void {
        $currentBorrowed = $user->getCurrentBorrowedBooksCount();
        $maxAllowed = $user->membershipType->max_books_allowed;
        $totalAfter = $currentBorrowed + $requestedBooks;

        if ($totalAfter > $maxAllowed) {
            throw ValidationException::withMessages([
                "books" => "Cannot borrow {$requestedBooks} book(s). User currently has {$currentBorrowed} book(s) borrowed. Maximum allowed: {$maxAllowed} (Membership: {$user->membershipType->name}).",
            ]);
        }
    }

    /**
     * Validate borrow duration
     *
     * @param User $user
     * @param int $borrowDays
     * @throws ValidationException
     */
    protected function validateBorrowDuration(User $user, int $borrowDays): void
    {
        $maxDays = $user->membershipType->max_borrow_days;

        if ($borrowDays > $maxDays) {
            throw ValidationException::withMessages([
                "borrow_days" => "Borrow duration ({$borrowDays} days) exceeds maximum allowed ({$maxDays} days) for {$user->membershipType->name} membership.",
            ]);
        }

        if ($borrowDays < 1) {
            throw ValidationException::withMessages([
                "borrow_days" => "Borrow duration must be at least 1 day.",
            ]);
        }
    }

    /**
     * Validate book availability
     *
     * @param array $bookIds
     * @throws ValidationException
     */
    protected function validateBookAvailability(array $bookIds): void
    {
        $unavailableBooks = [];

        foreach ($bookIds as $bookId) {
            $book = Book::find($bookId);

            if (!$book) {
                throw ValidationException::withMessages([
                    "books" => "Book with ID {$bookId} not found.",
                ]);
            }

            if ($book->stock <= 0) {
                $unavailableBooks[] = $book->title;
            }
        }

        if (!empty($unavailableBooks)) {
            $bookList = implode(", ", $unavailableBooks);
            throw ValidationException::withMessages([
                "books" => "The following books are not available: {$bookList}",
            ]);
        }
    }

    /**
     * Validate renewal eligibility
     *
     * @param Transaction $transaction
     * @return array
     */
    protected function validateRenewal(Transaction $transaction): array
    {
        $reasons = [];

        // Check if already returned
        if ($transaction->returned_date) {
            $reasons[] = "Transaction has already been returned";
        }

        // Check if overdue
        if ($transaction->isOverdue()) {
            $daysOverdue = $transaction->getDaysOverdue();
            $reasons[] = "Transaction is overdue by {$daysOverdue} day(s)";
        }

        // Check renewal limit
        $maxRenewals = $transaction->user->membershipType?->renewal_limit ?? 2;
        if ($transaction->renewed_count >= $maxRenewals) {
            $reasons[] = "Maximum renewals reached ({$transaction->renewed_count}/{$maxRenewals})";
        }

        // Check membership status
        if (!$transaction->user->hasActiveMembership()) {
            $reasons[] = "User membership has expired";
        }

        if (!empty($reasons)) {
            return [
                "can_renew" => false,
                "message" => "Cannot renew: " . implode(", ", $reasons),
                "reasons" => $reasons,
            ];
        }

        return [
            "can_renew" => true,
            "message" => "Transaction can be renewed",
            "reasons" => [],
        ];
    }

    /**
     * Determine return status based on conditions
     *
     * @param Transaction $transaction
     * @param Carbon $returnDate
     * @return BorrowedStatus
     */
    protected function determineReturnStatus(
        Transaction $transaction,
        Carbon $returnDate,
    ): BorrowedStatus {
        // Check if any items are lost
        if (
            $transaction->items->contains(
                fn($item) => $item->item_status === "lost",
            )
        ) {
            return BorrowedStatus::Lost;
        }

        // Check if any items are damaged
        if (
            $transaction->items->contains(
                fn($item) => $item->item_status === "damaged",
            )
        ) {
            return BorrowedStatus::Damaged;
        }

        // Check if returned late
        if ($returnDate->gt($transaction->due_date)) {
            return BorrowedStatus::Delayed;
        }

        // Returned on time
        return BorrowedStatus::Returned;
    }
}

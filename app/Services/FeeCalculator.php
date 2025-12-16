<?php

namespace App\Services;

use App\Models\Book;
use App\Models\TransactionItem;
use App\Settings\FeeSettings;
use Illuminate\Support\Carbon;

class FeeCalculator
{
    protected FeeSettings $feeSettings;

    public function __construct(FeeSettings $feeSettings)
    {
        $this->feeSettings = $feeSettings;
    }

    /**
     * Get fee settings
     *
     * @return FeeSettings
     */
    public function getFeeSettings(): FeeSettings
    {
        return $this->feeSettings;
    }

    /**
     * Calculate overdue fine for a transaction item
     *
     * @param TransactionItem $item
     * @param Carbon|null $returnDate
     * @return float Fine amount in dollars
     */
    public function calculateOverdueFine(
        TransactionItem $item,
        ?Carbon $returnDate = null,
    ): float {
        if (!$this->feeSettings->overdue_fee_enabled) {
            return 0;
        }

        $returnDate = $returnDate ?? $item->transaction->returned_date;

        if (!$returnDate) {
            return 0;
        }

        $dueDate = $item->due_date->startOfDay();
        $returnDate = Carbon::parse($returnDate)->startOfDay();

        if ($returnDate->lte($dueDate)) {
            return 0;
        }

        // Calculate days late (whole days only, no fractional days)
        $daysLate = $dueDate->diffInDays($returnDate);

        // Apply grace period
        if ($this->feeSettings->grace_period_days > 0) {
            $daysLate -= $this->feeSettings->grace_period_days;
            if ($daysLate <= 0) {
                return 0;
            }
        }

        // Apply maximum days cap if set
        if ($this->feeSettings->overdue_fee_max_days !== null) {
            $daysLate = min(
                $daysLate,
                $this->feeSettings->overdue_fee_max_days,
            );
        }

        // Calculate fine (fee_per_day is in dollars)
        $fine = $daysLate * $this->feeSettings->overdue_fee_per_day;

        // Apply maximum amount cap if set
        if ($this->feeSettings->overdue_fee_max_amount !== null) {
            $fine = min($fine, $this->feeSettings->overdue_fee_max_amount);
        }

        // Apply small amount waiver if enabled
        if (
            $this->feeSettings->waive_small_amounts &&
            $fine < $this->feeSettings->small_amount_threshold
        ) {
            return 0;
        }

        return $fine; // Return in dollars - MoneyCast will convert to cents for storage
    }

    /**
     * Calculate lost book fine
     *
     * @param Book $book
     * @return float Fine amount in dollars
     */
    public function calculateLostBookFine(Book $book): float
    {
        $fine = 0;

        if ($this->feeSettings->lost_book_fine_type === "percentage") {
            // Calculate based on book price (MoneyCast already returns it in dollars)
            $bookPriceInDollars = $book->price ?? 0;
            $fine =
                ($bookPriceInDollars *
                    $this->feeSettings->lost_book_fine_rate) /
                100;

            // Apply minimum fine if set
            if (
                $this->feeSettings->lost_book_minimum_fine !== null &&
                $fine < $this->feeSettings->lost_book_minimum_fine
            ) {
                $fine = $this->feeSettings->lost_book_minimum_fine;
            }

            // Apply maximum fine if set
            if (
                $this->feeSettings->lost_book_maximum_fine !== null &&
                $fine > $this->feeSettings->lost_book_maximum_fine
            ) {
                $fine = $this->feeSettings->lost_book_maximum_fine;
            }
        } else {
            // Fixed amount
            $fine = $this->feeSettings->lost_book_fine_rate;
        }

        return $fine; // Return in dollars - MoneyCast will convert to cents for storage
    }

    /**
     * Calculate current overdue fine for items that haven't been returned yet
     *
     * @param TransactionItem $item
     * @return float Fine amount in dollars if returned today
     */
    public function calculateCurrentOverdueFine(TransactionItem $item): float
    {
        if ($item->transaction->returned_date) {
            return $item->fine ?? 0;
        }

        return $this->calculateOverdueFine($item, now());
    }

    /**
     * Format fine amount for display
     *
     * @param float $amountInDollars
     * @return string Formatted amount with currency symbol
     */
    public function formatFine(float $amountInDollars): string
    {
        return $this->feeSettings->currency_symbol .
            number_format($amountInDollars, 2);
    }

    /**
     * Get fee summary for display
     *
     * @return array
     */
    public function getFeeSummary(): array
    {
        return [
            "overdue_enabled" => $this->feeSettings->overdue_fee_enabled,
            "overdue_per_day" => $this->feeSettings->overdue_fee_per_day,
            "grace_period" => $this->feeSettings->grace_period_days,
            "lost_book_type" => $this->feeSettings->lost_book_fine_type,
            "lost_book_rate" => $this->feeSettings->lost_book_fine_rate,
            "currency_symbol" => $this->feeSettings->currency_symbol,
            "currency_code" => $this->feeSettings->currency_code,
        ];
    }

    /**
     * Check if a fine amount should be waived
     *
     * @param float $amount
     * @return bool
     */
    public function shouldWaiveFine(float $amount): bool
    {
        if (!$this->feeSettings->waive_small_amounts) {
            return false;
        }

        return $amount < $this->feeSettings->small_amount_threshold;
    }

    /**
     * Calculate total outstanding fines for a user
     *
     * @param \App\Models\User $user
     * @return float Total fines in dollars
     */
    public function calculateUserTotalFines($user): float
    {
        $totalFines = 0;

        foreach ($user->transactions as $transaction) {
            if ($transaction->returned_date) {
                // For returned transactions, use the stored fine
                $totalFines += $transaction->total_fine ?? 0;
            } else {
                // For active transactions, calculate current overdue
                foreach ($transaction->items as $item) {
                    $totalFines += $this->calculateCurrentOverdueFine($item);
                }
            }
        }

        return $totalFines;
    }

    /**
     * Calculate total fine for a transaction
     *
     * @param \App\Models\Transaction $transaction
     * @return float Total fines in dollars
     */
    public function calculateTransactionTotalFine($transaction): float
    {
        if (!$transaction->returned_date) {
            // For active transactions, calculate current overdue
            return $transaction->items->sum(function ($item) {
                return $this->calculateCurrentOverdueFine($item);
            });
        }

        // For returned transactions, sum the stored fines
        return $transaction->items->sum("fine");
    }

    /**
     * Update all fines for a transaction's items
     *
     * @param \App\Models\Transaction $transaction
     * @return void
     */
    public function updateTransactionFines($transaction): void
    {
        foreach ($transaction->items as $item) {
            $fine = $this->calculateOverdueFine($item);
            $item->update(["fine" => $fine]);
        }
    }

    /**
     * Get detailed fee breakdown for a transaction
     *
     * @param \App\Models\Transaction $transaction
     * @return array
     */
    public function getTransactionFeeBreakdown($transaction): array
    {
        $breakdown = [
            "items" => [],
            "total" => 0,
            "currency_symbol" => $this->feeSettings->currency_symbol,
        ];

        foreach ($transaction->items as $item) {
            $fine = $transaction->returned_date
                ? $item->fine ?? 0
                : $this->calculateCurrentOverdueFine($item);

            $breakdown["items"][] = [
                "book_title" => $item->book->title ?? "Unknown",
                "fine" => $fine,
                "formatted_fine" => $this->formatFine($fine),
            ];

            $breakdown["total"] += $fine;
        }

        $breakdown["formatted_total"] = $this->formatFine($breakdown["total"]);

        return $breakdown;
    }
}

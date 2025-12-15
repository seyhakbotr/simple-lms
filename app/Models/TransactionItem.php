<?php

namespace App\Models;

use App\Settings\FeeSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TransactionItem extends Model
{
    use HasFactory;

    protected $table = "transaction_items";

    protected $fillable = ["transaction_id", "book_id", "borrowed_for", "fine"];

    protected $casts = [
        "borrowed_for" => "integer",
        "fine" => "integer",
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Calculate the due date for this item
     */
    public function getDueDateAttribute(): Carbon
    {
        return Carbon::parse($this->transaction->borrowed_date)->addDays(
            $this->borrowed_for,
        );
    }

    /**
     * Calculate fine for this item if returned late
     */
    public function calculateFine(): int
    {
        if (!$this->transaction->returned_date) {
            return 0;
        }

        $feeSettings = app(FeeSettings::class);

        // Check if overdue fees are enabled
        if (!$feeSettings->overdue_fee_enabled) {
            return 0;
        }

        $dueDate = $this->due_date;
        $returnDate = Carbon::parse($this->transaction->returned_date);

        if ($returnDate->gt($dueDate)) {
            // Calculate days late
            $daysLate = $dueDate->diffInDays($returnDate);

            // Apply grace period
            if ($feeSettings->grace_period_days > 0) {
                $daysLate -= $feeSettings->grace_period_days;
                if ($daysLate <= 0) {
                    return 0;
                }
            }

            // Apply maximum days cap if set
            if ($feeSettings->overdue_fee_max_days !== null) {
                $daysLate = min($daysLate, $feeSettings->overdue_fee_max_days);
            }

            // Calculate fine
            $fine = $daysLate * $feeSettings->overdue_fee_per_day;

            // Apply maximum amount cap if set
            if ($feeSettings->overdue_fee_max_amount !== null) {
                $fine = min($fine, $feeSettings->overdue_fee_max_amount);
            }

            // Apply small amount waiver if enabled
            if (
                $feeSettings->waive_small_amounts &&
                $fine < $feeSettings->small_amount_threshold
            ) {
                return 0;
            }

            return (int) round($fine * 100); // Convert to cents
        }

        return 0;
    }
}

<?php

namespace App\Models;

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

        $dueDate = $this->due_date;
        $returnDate = Carbon::parse($this->transaction->returned_date);

        if ($returnDate->gt($dueDate)) {
            $delay = $dueDate->diffInDays($returnDate);
            return $delay * 10; // $10 per day
        }

        return 0;
    }
}

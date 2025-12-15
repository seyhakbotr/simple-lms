<?php

namespace App\Models;

use App\Enums\BorrowedStatus;
use App\Observers\TransactionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

#[ObservedBy(TransactionObserver::class)]
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "borrowed_date",
        "due_date",
        "returned_date",
        "renewed_count",
        "status",
    ];

    protected $casts = [
        "status" => BorrowedStatus::class,
        "borrowed_date" => "date",
        "due_date" => "date",
        "returned_date" => "date",
        "renewed_count" => "integer",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * Get all books associated with this transaction
     */
    public function books()
    {
        return $this->hasManyThrough(
            Book::class,
            TransactionItem::class,
            "transaction_id",
            "id",
            "id",
            "book_id",
        );
    }

    /**
     * Calculate total fine for all items in this transaction
     */
    public function getTotalFineAttribute(): int
    {
        if (!$this->returned_date) {
            return 0;
        }

        return $this->items->sum(function ($item) {
            return $item->calculateFine();
        });
    }

    /**
     * Check if transaction is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->returned_date || !$this->due_date) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Get days overdue
     */
    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return now()->diffInDays($this->due_date);
    }

    /**
     * Check if transaction can be renewed
     */
    public function canRenew(): bool
    {
        // Cannot renew if already returned
        if ($this->returned_date) {
            return false;
        }

        // Cannot renew if overdue
        if ($this->isOverdue()) {
            return false;
        }

        // Get user's membership type renewal limit
        $maxRenewals = $this->user->membershipType?->renewal_limit ?? 2;

        return $this->renewed_count < $maxRenewals;
    }

    /**
     * Renew the transaction
     */
    public function renew(): bool
    {
        if (!$this->canRenew()) {
            return false;
        }

        // Get renewal period from membership type
        $renewalDays = $this->user->membershipType?->max_borrow_days ?? 14;

        $this->update([
            "due_date" => $this->due_date->addDays($renewalDays),
            "renewed_count" => $this->renewed_count + 1,
        ]);

        return true;
    }

    /**
     * Calculate due date based on membership type and borrowed items
     */
    public function calculateDueDate(): Carbon
    {
        $maxBorrowDays = $this->user->membershipType?->max_borrow_days ?? 14;

        // Get the longest borrowed_for from items, or use membership default
        $longestBorrowPeriod =
            $this->items->max("borrowed_for") ?? $maxBorrowDays;

        return Carbon::parse($this->borrowed_date)->addDays(
            $longestBorrowPeriod,
        );
    }

    public static function booted(): void
    {
        parent::boot();

        static::creating(function ($transaction) {
            // Auto-calculate due_date if not set
            if (!$transaction->due_date) {
                $maxBorrowDays =
                    $transaction->user->membershipType?->max_borrow_days ?? 14;
                $transaction->due_date = Carbon::parse(
                    $transaction->borrowed_date,
                )->addDays($maxBorrowDays);
            }
        });

        static::saving(function ($transaction) {
            // Calculate and update fines for all items when returned_date is set
            if (
                $transaction->returned_date &&
                $transaction->isDirty("returned_date")
            ) {
                // This will be handled after the transaction is saved
                // We'll use the 'saved' event for items
            }
        });

        static::saved(function ($transaction) {
            // Update fines for all items when returned_date changes
            if ($transaction->returned_date) {
                foreach ($transaction->items as $item) {
                    $fine = $item->calculateFine();
                    $item->update(["fine" => $fine]);
                }
            }
        });

        static::created(function ($model) {
            $cacheKey =
                "NavigationCount_" .
                class_basename($model) .
                $model->getTable();
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }
        });

        static::deleted(function ($model) {
            $cacheKey =
                "NavigationCount_" .
                class_basename($model) .
                $model->getTable();
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }
        });
    }
}

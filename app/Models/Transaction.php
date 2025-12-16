<?php

namespace App\Models;

use App\Enums\BorrowedStatus;
use App\Enums\LifecycleStatus;
use App\Observers\TransactionObserver;
use App\Services\FeeCalculator;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

#[ObservedBy(TransactionObserver::class)]
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "reference_no",
        "borrowed_date",
        "due_date",
        "returned_date",
        "renewed_count",
        "status",
        "lifecycle_status",
    ];

    protected $casts = [
        "status" => BorrowedStatus::class,
        "lifecycle_status" => LifecycleStatus::class,
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
     * Get the invoice for this transaction
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
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
     * Uses stored fines if transaction is returned, otherwise calculates current overdue
     */
    public function getTotalFineAttribute(): float
    {
        if (!$this->returned_date) {
            // For active transactions, calculate current overdue
            return $this->items->sum(function ($item) {
                return $item->getCurrentOverdueFine();
            });
        }

        // For returned transactions, use stored fines (MoneyCast returns as dollars)
        return $this->items->sum("total_fine");
    }

    /**
     * Update fines for all items in this transaction
     */
    public function updateFines(): void
    {
        foreach ($this->items as $item) {
            $item->updateFine();
        }
    }

    /**
     * Get formatted total fine for display
     */
    public function getFormattedTotalFineAttribute(): string
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->formatFine($this->total_fine);
    }

    /**
     * Get breakdown of all fee types
     */
    public function getFeeBreakdownAttribute(): array
    {
        $overdueFine = $this->items->sum("overdue_fine");
        $lostFine = $this->items->sum("lost_fine");
        $damageFine = $this->items->sum("damage_fine");
        $total = $overdueFine + $lostFine + $damageFine;

        $feeCalculator = app(FeeCalculator::class);

        return [
            "overdue" => [
                "amount" => $overdueFine,
                "formatted" => $feeCalculator->formatFine($overdueFine),
            ],
            "lost" => [
                "amount" => $lostFine,
                "formatted" => $feeCalculator->formatFine($lostFine),
            ],
            "damage" => [
                "amount" => $damageFine,
                "formatted" => $feeCalculator->formatFine($damageFine),
            ],
            "total" => [
                "amount" => $total,
                "formatted" => $feeCalculator->formatFine($total),
            ],
        ];
    }

    /**
     * Check if transaction has any lost items
     */
    public function hasLostItems(): bool
    {
        return $this->items->contains(fn($item) => $item->isLost());
    }

    /**
     * Check if transaction has any damaged items
     */
    public function hasDamagedItems(): bool
    {
        return $this->items->contains(fn($item) => $item->isDamaged());
    }

    /**
     * Check if all items in transaction are returned
     */
    public function allItemsReturned(): bool
    {
        return $this->items->every(
            fn($item) => $item->lifecycle_status === "returned",
        );
    }

    /**
     * Check if transaction has mixed status (some returned, some lost)
     */
    public function hasMixedStatus(): bool
    {
        $statuses = $this->items->pluck("lifecycle_status")->unique();
        return $statuses->count() > 1;
    }

    /**
     * Get count of items by lifecycle status
     */
    public function getItemStatusCounts(): array
    {
        return [
            "active" => $this->items
                ->where("lifecycle_status", "active")
                ->count(),
            "returned" => $this->items
                ->where("lifecycle_status", "returned")
                ->count(),
            "lost" => $this->items->where("lifecycle_status", "lost")->count(),
        ];
    }

    /**
     * Update transaction lifecycle status based on items
     */
    public function updateLifecycleStatus(): void
    {
        $counts = $this->getItemStatusCounts();

        // All items lost
        if (
            $counts["lost"] > 0 &&
            $counts["active"] === 0 &&
            $counts["returned"] === 0
        ) {
            $this->lifecycle_status = LifecycleStatus::Completed;
        }
        // All items returned
        elseif (
            $counts["returned"] > 0 &&
            $counts["active"] === 0 &&
            $counts["lost"] === 0
        ) {
            $this->lifecycle_status = LifecycleStatus::Completed;
        }
        // Mix of returned and lost (partial completion)
        elseif (
            $counts["active"] === 0 &&
            ($counts["returned"] > 0 || $counts["lost"] > 0)
        ) {
            $this->lifecycle_status = LifecycleStatus::Completed;
        }
        // Still has active items
        elseif ($counts["active"] > 0) {
            $this->lifecycle_status = LifecycleStatus::Active;
        }

        $this->save();
    }

    /**
     * Check if transaction is active
     */
    public function isActive(): bool
    {
        return $this->lifecycle_status === LifecycleStatus::Active;
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->lifecycle_status?->isCompleted() ?? false;
    }

    /**
     * Scope query to active transactions
     */
    public function scopeActive($query)
    {
        return $query->where("lifecycle_status", LifecycleStatus::Active);
    }

    /**
     * Scope query to completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where("lifecycle_status", LifecycleStatus::Completed);
    }

    /**
     * Scope query to overdue active transactions
     */
    public function scopeOverdue($query)
    {
        return $query
            ->where("lifecycle_status", LifecycleStatus::Active)
            ->where("due_date", "<", now());
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

    /**
     * Generate a unique reference number for the transaction
     */
    public static function generateReferenceNo(): string
    {
        do {
            // Format: TXN-YYYYMMDD-XXXX (e.g., TXN-20250115-0001)
            $date = now()->format("Ymd");
            $random = str_pad(rand(0, 9999), 4, "0", STR_PAD_LEFT);
            $referenceNo = "TXN-{$date}-{$random}";
        } while (self::where("reference_no", $referenceNo)->exists());

        return $referenceNo;
    }

    public static function booted(): void
    {
        parent::boot();

        static::creating(function ($transaction) {
            // Auto-generate reference_no if not set
            if (!$transaction->reference_no) {
                $transaction->reference_no = self::generateReferenceNo();
            }

            // Auto-calculate due_date if not set
            if (!$transaction->due_date) {
                $maxBorrowDays =
                    $transaction->user->membershipType?->max_borrow_days ?? 14;
                $transaction->due_date = Carbon::parse(
                    $transaction->borrowed_date,
                )->addDays($maxBorrowDays);
            }

            // Set default lifecycle status
            if (!$transaction->lifecycle_status) {
                $transaction->lifecycle_status = LifecycleStatus::Active;
            }
        });

        static::saved(function ($transaction) {
            // Don't auto-update fines - TransactionService handles this properly
            // Removing this prevents overwriting fees with zeros after they're set

            // Skip lifecycle update during initial creation
            if ($transaction->wasRecentlyCreated) {
                return;
            }

            // Update status to Lost or Damaged if items are marked as such
            if (
                $transaction->hasLostItems() &&
                $transaction->status !== BorrowedStatus::Lost
            ) {
                $transaction->update(["status" => BorrowedStatus::Lost]);
            } elseif (
                $transaction->hasDamagedItems() &&
                $transaction->status !== BorrowedStatus::Damaged
            ) {
                $transaction->update(["status" => BorrowedStatus::Damaged]);
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

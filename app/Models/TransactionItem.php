<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Services\FeeCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TransactionItem extends Model
{
    use HasFactory;

    protected $table = "transaction_items";

    protected $fillable = [
        "transaction_id",
        "book_id",
        "borrowed_for",
        "fine", // Legacy - for backward compatibility
        "item_status",
        "overdue_fine",
        "lost_fine",
        "damage_fine",
        "damage_notes",
        "total_fine",
    ];

    protected $casts = [
        "borrowed_for" => "integer",
        "fine" => MoneyCast::class, // Legacy - stores as cents, works as dollars
        "overdue_fine" => MoneyCast::class, // Stores as cents, works as dollars
        "lost_fine" => MoneyCast::class, // Stores as cents, works as dollars
        "damage_fine" => MoneyCast::class, // Stores as cents, works as dollars
        "total_fine" => MoneyCast::class, // Stores as cents, works as dollars
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
     * Uses the transaction's due_date as all items in a transaction share the same due date
     */
    public function getDueDateAttribute(): Carbon
    {
        return Carbon::parse($this->transaction->due_date);
    }

    /**
     * Calculate overdue fine for this item using the centralized FeeCalculator service
     */
    public function calculateOverdueFine(): int
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->calculateOverdueFine($this);
    }

    /**
     * Calculate lost book fine for this item
     */
    public function calculateLostBookFine(): int
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->calculateLostBookFine($this->book);
    }

    /**
     * Calculate total fine (all fee types combined)
     */
    public function calculateTotalFine(): int
    {
        return $this->overdue_fine + $this->lost_fine + $this->damage_fine;
    }

    /**
     * Legacy method for backward compatibility
     */
    public function calculateFine(): int
    {
        return $this->calculateOverdueFine();
    }

    /**
     * Get the current overdue fine (if not yet returned, calculates as if returned today)
     */
    public function getCurrentOverdueFine(): int
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->calculateCurrentOverdueFine($this);
    }

    /**
     * Update and save all fines for this item based on status
     */
    public function updateFines(): void
    {
        $overdueFine = 0;
        $lostFine = 0;
        $damageFine = 0;

        // Calculate overdue fine if returned late
        if ($this->transaction->returned_date) {
            $overdueFine = $this->calculateOverdueFine();
        }

        // Calculate lost book fine if marked as lost
        if ($this->item_status === "lost") {
            $lostFine = $this->calculateLostBookFine();
        }

        // Damage fine is set manually
        $damageFine = $this->damage_fine ?? 0;

        $totalFine = $overdueFine + $lostFine + $damageFine;

        $this->update([
            "overdue_fine" => $overdueFine,
            "lost_fine" => $lostFine,
            "damage_fine" => $damageFine,
            "total_fine" => $totalFine,
            "fine" => $totalFine, // Legacy field
        ]);
    }

    /**
     * Legacy method for backward compatibility
     */
    public function updateFine(): void
    {
        $this->updateFines();
    }

    /**
     * Get formatted total fine amount for display
     */
    public function getFormattedFineAttribute(): string
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->formatFine(
            $this->total_fine ?? ($this->fine ?? 0),
        );
    }

    /**
     * Get formatted overdue fine
     */
    public function getFormattedOverdueFineAttribute(): string
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->formatFine($this->overdue_fine ?? 0);
    }

    /**
     * Get formatted lost fine
     */
    public function getFormattedLostFineAttribute(): string
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->formatFine($this->lost_fine ?? 0);
    }

    /**
     * Get formatted damage fine
     */
    public function getFormattedDamageFineAttribute(): string
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->formatFine($this->damage_fine ?? 0);
    }

    /**
     * Get fee breakdown for this item
     */
    public function getFeeBreakdown(): array
    {
        return [
            "overdue" => [
                "amount" => $this->overdue_fine ?? 0,
                "formatted" => $this->formatted_overdue_fine,
            ],
            "lost" => [
                "amount" => $this->lost_fine ?? 0,
                "formatted" => $this->formatted_lost_fine,
            ],
            "damage" => [
                "amount" => $this->damage_fine ?? 0,
                "formatted" => $this->formatted_damage_fine,
            ],
            "total" => [
                "amount" => $this->total_fine ?? ($this->fine ?? 0),
                "formatted" => $this->formatted_fine,
            ],
        ];
    }

    /**
     * Check if item has any fines
     */
    public function hasFines(): bool
    {
        return ($this->total_fine ?? ($this->fine ?? 0)) > 0;
    }

    /**
     * Check if item is lost
     */
    public function isLost(): bool
    {
        return $this->item_status === "lost";
    }

    /**
     * Check if item is damaged
     */
    public function isDamaged(): bool
    {
        return $this->item_status === "damaged";
    }

    /**
     * Mark item as lost and calculate fine
     */
    public function markAsLost(): void
    {
        $this->update([
            "item_status" => "lost",
            "lost_fine" => $this->calculateLostBookFine(),
        ]);
        $this->updateFines();
    }

    /**
     * Mark item as damaged with optional fine amount
     */
    public function markAsDamaged(
        int $damageFineAmount = 0,
        ?string $notes = null,
    ): void {
        $this->update([
            "item_status" => "damaged",
            "damage_fine" => $damageFineAmount,
            "damage_notes" => $notes,
        ]);
        $this->updateFines();
    }
}

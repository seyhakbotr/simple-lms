<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Services\FeeCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        "invoice_number",
        "transaction_id",
        "membership_type_id",
        "user_id",
        "overdue_fee",
        "lost_fee",
        "damage_fee",
        "total_amount",
        "amount_paid",
        "amount_due",
        "status",
        "invoice_date",
        "due_date",
        "paid_at",
        "notes",
    ];

    protected $casts = [
        "overdue_fee" => MoneyCast::class,
        "lost_fee" => MoneyCast::class,
        "damage_fee" => MoneyCast::class,
        "total_amount" => MoneyCast::class,
        "amount_paid" => MoneyCast::class,
        "amount_due" => MoneyCast::class,
        "invoice_date" => "date",
        "due_date" => "date",
        "paid_at" => "datetime",
    ];

    /**
     * Relationship to transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Relationship to membership type
     */
    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }

    /**
     * Relationship to user (borrower)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source of the invoice (transaction, membership, etc.)
     */
    public function getSourceAttribute(): string
    {
        if ($this->transaction) {
            return $this->transaction->reference_no;
        }

        if ($this->membershipType) {
            return $this->membershipType->name . ' Membership';
        }

        return 'N/A';
    }

    /**
     * Generate a unique invoice number
     * Format: INV-YYYYMMDD-XXXX (e.g., INV-20251216-0001)
     */
    public static function generateInvoiceNumber(): string
    {
        do {
            $date = now()->format("Ymd");
            $random = str_pad(rand(0, 9999), 4, "0", STR_PAD_LEFT);
            $invoiceNumber = "INV-{$date}-{$random}";
        } while (self::where("invoice_number", $invoiceNumber)->exists());

        return $invoiceNumber;
    }

    /**
     * Check if invoice is fully paid
     */
    public function isPaid(): bool
    {
        return $this->status === "paid";
    }

    /**
     * Check if invoice is partially paid
     */
    public function isPartiallyPaid(): bool
    {
        return $this->status === "partially_paid";
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->isPaid() || $this->status === "waived") {
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
     * Record a payment
     */
    public function recordPayment(float $amount, ?string $notes = null): void
    {
        // Work entirely in dollars - MoneyCast handles conversion to cents
        $newAmountPaid = $this->amount_paid + $amount;
        $totalAmount = $this->total_amount;

        $this->update([
            "amount_paid" => $newAmountPaid, // MoneyCast converts to cents automatically
            "amount_due" => max(0, $totalAmount - $newAmountPaid), // MoneyCast converts to cents automatically
            "status" => $this->determineStatus($newAmountPaid, $totalAmount),
            "paid_at" =>
                $newAmountPaid >= $totalAmount ? now() : $this->paid_at,
            "notes" => $notes
                ? ($this->notes
                    ? $this->notes . "\n" . $notes
                    : $notes)
                : $this->notes,
        ]);
    }

    /**
     * Determine invoice status based on payment
     */
    protected function determineStatus(
        float $amountPaid,
        float $totalAmount,
    ): string {
        if ($amountPaid >= $totalAmount) {
            return "paid";
        }

        if ($amountPaid > 0) {
            return "partially_paid";
        }

        return "unpaid";
    }

    /**
     * Waive the invoice
     */
    public function waive(?string $reason = null): void
    {
        $this->update([
            "status" => "waived",
            "amount_due" => 0,
            "notes" => $reason
                ? ($this->notes
                    ? $this->notes . "\n" . $reason
                    : $reason)
                : $this->notes,
        ]);
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->formatFine($this->total_amount);
    }

    /**
     * Get formatted amount paid
     */
    public function getFormattedAmountPaidAttribute(): string
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->formatFine($this->amount_paid);
    }

    /**
     * Get formatted amount due
     */
    public function getFormattedAmountDueAttribute(): string
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->formatFine($this->amount_due);
    }

    /**
     * Get formatted overdue fee
     */
    public function getFormattedOverdueFeeAttribute(): string
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->formatFine($this->overdue_fee);
    }

    /**
     * Get formatted lost fee
     */
    public function getFormattedLostFeeAttribute(): string
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->formatFine($this->lost_fee);
    }

    /**
     * Get formatted damage fee
     */
    public function getFormattedDamageFeeAttribute(): string
    {
        $feeCalculator = app(FeeCalculator::class);
        return $feeCalculator->formatFine($this->damage_fee);
    }

    /**
     * Get fee breakdown array
     */
    public function getFeeBreakdownAttribute(): array
    {
        $feeCalculator = app(FeeCalculator::class);

        return [
            "overdue" => [
                "amount" => $this->overdue_fee,
                "formatted" => $feeCalculator->formatFine($this->overdue_fee),
            ],
            "lost" => [
                "amount" => $this->lost_fee,
                "formatted" => $feeCalculator->formatFine($this->lost_fee),
            ],
            "damage" => [
                "amount" => $this->damage_fee,
                "formatted" => $feeCalculator->formatFine($this->damage_fee),
            ],
            "total" => [
                "amount" => $this->total_amount,
                "formatted" => $feeCalculator->formatFine($this->total_amount),
            ],
        ];
    }

    /**
     * Boot method to handle model events
     */
    protected static function booted(): void
    {
        static::creating(function ($invoice) {
            // Auto-generate invoice number if not set
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }

            // Set invoice date to today if not set
            if (!$invoice->invoice_date) {
                $invoice->invoice_date = now();
            }

            // Set due date to 30 days from invoice date if not set
            if (!$invoice->due_date) {
                $invoice->due_date = Carbon::parse(
                    $invoice->invoice_date,
                )->addDays(30);
            }

            // Initialize amount_due from total_amount if not set
            if (!isset($invoice->amount_due)) {
                $invoice->amount_due = $invoice->total_amount;
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

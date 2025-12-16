<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function __construct(protected FeeCalculator $feeCalculator)
    {
    }

    /**
     * Generate an invoice for a returned transaction
     *
     * @param Transaction $transaction
     * @param int $paymentDueDays Number of days until payment is due (default: 30)
     * @return Invoice|null Returns null if no fees to invoice
     */
    public function generateInvoiceForTransaction(
        Transaction $transaction,
        int $paymentDueDays = 30,
    ): ?Invoice {
        // Only generate invoice if transaction is returned
        if (!$transaction->returned_date) {
            Log::warning(
                "Cannot generate invoice for transaction {$transaction->reference_no} - not returned yet",
            );
            return null;
        }

        // Check if invoice already exists
        if ($transaction->invoice) {
            Log::info(
                "Invoice already exists for transaction {$transaction->reference_no}",
            );
            return $transaction->invoice;
        }

        // Calculate total fees from transaction items
        $feeBreakdown = $this->calculateFeeBreakdown($transaction);

        // Don't create invoice if total is zero
        if ($feeBreakdown["total"] <= 0) {
            Log::info(
                "No fees to invoice for transaction {$transaction->reference_no}",
            );
            return null;
        }

        return DB::transaction(function () use (
            $transaction,
            $feeBreakdown,
            $paymentDueDays,
        ) {
            $invoice = Invoice::create([
                "transaction_id" => $transaction->id,
                "user_id" => $transaction->user_id,
                "overdue_fee" => $feeBreakdown["overdue"],
                "lost_fee" => $feeBreakdown["lost"],
                "damage_fee" => $feeBreakdown["damage"],
                "total_amount" => $feeBreakdown["total"],
                "amount_paid" => 0,
                "amount_due" => $feeBreakdown["total"],
                "status" => "unpaid",
                "invoice_date" => $transaction->returned_date,
                "due_date" => Carbon::parse($transaction->returned_date)->addDays(
                    $paymentDueDays,
                ),
            ]);

            Log::info(
                "Invoice {$invoice->invoice_number} generated for transaction {$transaction->reference_no}",
                [
                    "total_amount" => $feeBreakdown["total"],
                    "overdue_fee" => $feeBreakdown["overdue"],
                    "lost_fee" => $feeBreakdown["lost"],
                    "damage_fee" => $feeBreakdown["damage"],
                ],
            );

            return $invoice;
        });
    }

    /**
     * Calculate fee breakdown from transaction items
     *
     * @param Transaction $transaction
     * @return array Array with overdue, lost, damage, and total fees (in cents)
     */
    protected function calculateFeeBreakdown(Transaction $transaction): array
    {
        $overdueFee = 0;
        $lostFee = 0;
        $damageFee = 0;

        foreach ($transaction->items as $item) {
            // Get raw values in cents
            $overdueFee += $item->getRawOriginal("overdue_fine") ?? 0;
            $lostFee += $item->getRawOriginal("lost_fine") ?? 0;
            $damageFee += $item->getRawOriginal("damage_fine") ?? 0;
        }

        $total = $overdueFee + $lostFee + $damageFee;

        return [
            "overdue" => $overdueFee,
            "lost" => $lostFee,
            "damage" => $damageFee,
            "total" => $total,
        ];
    }

    /**
     * Record a payment for an invoice
     *
     * @param Invoice $invoice
     * @param float $amount Amount in dollars
     * @param string|null $paymentMethod
     * @param string|null $notes
     * @return Invoice
     */
    public function recordPayment(
        Invoice $invoice,
        float $amount,
        ?string $paymentMethod = null,
        ?string $notes = null,
    ): Invoice {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Payment amount must be positive");
        }

        if ($invoice->isPaid()) {
            throw new \InvalidArgumentException(
                "Invoice is already fully paid",
            );
        }

        // Ensure we don't overpay
        $maxPayment = $invoice->amount_due;
        if ($amount > $maxPayment) {
            throw new \InvalidArgumentException(
                "Payment amount exceeds amount due. Maximum: " .
                    $this->feeCalculator->formatFine($maxPayment),
            );
        }

        $paymentNote = "Payment received: " . $this->feeCalculator->formatFine($amount);
        if ($paymentMethod) {
            $paymentNote .= " via {$paymentMethod}";
        }
        if ($notes) {
            $paymentNote .= " - {$notes}";
        }

        $invoice->recordPayment($amount, $paymentNote);

        Log::info("Payment recorded for invoice {$invoice->invoice_number}", [
            "amount" => $amount,
            "payment_method" => $paymentMethod,
            "new_status" => $invoice->status,
        ]);

        return $invoice->fresh();
    }

    /**
     * Waive an invoice
     *
     * @param Invoice $invoice
     * @param string|null $reason
     * @return Invoice
     */
    public function waiveInvoice(Invoice $invoice, ?string $reason = null): Invoice
    {
        if ($invoice->isPaid()) {
            throw new \InvalidArgumentException(
                "Cannot waive a paid invoice",
            );
        }

        $waiveNote = "Invoice waived";
        if ($reason) {
            $waiveNote .= " - Reason: {$reason}";
        }

        $invoice->waive($waiveNote);

        Log::info("Invoice {$invoice->invoice_number} waived", [
            "reason" => $reason,
        ]);

        return $invoice->fresh();
    }

    /**
     * Get all unpaid invoices for a user
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnpaidInvoices(User $user)
    {
        return Invoice::where("user_id", $user->id)
            ->whereIn("status", ["unpaid", "partially_paid"])
            ->with(["transaction.items.book"])
            ->orderBy("invoice_date", "desc")
            ->get();
    }

    /**
     * Get overdue invoices for a user
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverdueInvoices(User $user)
    {
        return Invoice::where("user_id", $user->id)
            ->whereIn("status", ["unpaid", "partially_paid"])
            ->where("due_date", "<", now())
            ->with(["transaction.items.book"])
            ->orderBy("due_date", "asc")
            ->get();
    }

    /**
     * Get total outstanding balance for a user
     *
     * @param User $user
     * @return float Amount in dollars
     */
    public function getUserOutstandingBalance(User $user): float
    {
        $totalInCents = Invoice::where("user_id", $user->id)
            ->whereIn("status", ["unpaid", "partially_paid"])
            ->sum("amount_due");

        return $totalInCents / 100;
    }

    /**
     * Get invoice summary for a user
     *
     * @param User $user
     * @return array
     */
    public function getUserInvoiceSummary(User $user): array
    {
        $unpaidCount = Invoice::where("user_id", $user->id)
            ->where("status", "unpaid")
            ->count();

        $partiallyPaidCount = Invoice::where("user_id", $user->id)
            ->where("status", "partially_paid")
            ->count();

        $overdueCount = Invoice::where("user_id", $user->id)
            ->whereIn("status", ["unpaid", "partially_paid"])
            ->where("due_date", "<", now())
            ->count();

        $outstandingBalance = $this->getUserOutstandingBalance($user);

        return [
            "unpaid_count" => $unpaidCount,
            "partially_paid_count" => $partiallyPaidCount,
            "overdue_count" => $overdueCount,
            "outstanding_balance" => $outstandingBalance,
            "formatted_balance" => $this->feeCalculator->formatFine(
                $outstandingBalance,
            ),
            "has_overdue" => $overdueCount > 0,
        ];
    }

    /**
     * Generate invoice data for PDF/printing
     *
     * @param Invoice $invoice
     * @return array
     */
    public function getInvoiceData(Invoice $invoice): array
    {
        $transaction = $invoice->transaction->load([
            "items.book",
            "user.membershipType",
        ]);

        return [
            "invoice_number" => $invoice->invoice_number,
            "invoice_date" => $invoice->invoice_date->format("M d, Y"),
            "due_date" => $invoice->due_date->format("M d, Y"),
            "status" => ucfirst($invoice->status),
            "is_overdue" => $invoice->isOverdue(),
            "days_overdue" => $invoice->getDaysOverdue(),
            "borrower" => [
                "name" => $transaction->user->name,
                "email" => $transaction->user->email,
                "membership_type" =>
                    $transaction->user->membershipType?->name ?? "N/A",
            ],
            "transaction" => [
                "reference_no" => $transaction->reference_no,
                "borrowed_date" => $transaction->borrowed_date->format("M d, Y"),
                "due_date" => $transaction->due_date->format("M d, Y"),
                "returned_date" => $transaction->returned_date->format("M d, Y"),
                "status" => $transaction->status->value,
            ],
            "items" => $transaction->items->map(function ($item) {
                return [
                    "book_title" => $item->book->title,
                    "isbn" => $item->book->isbn,
                    "overdue_fine" => $this->feeCalculator->formatFine(
                        $item->overdue_fine,
                    ),
                    "lost_fine" => $this->feeCalculator->formatFine(
                        $item->lost_fine,
                    ),
                    "damage_fine" => $this->feeCalculator->formatFine(
                        $item->damage_fine,
                    ),
                    "item_status" => $item->item_status,
                    "damage_notes" => $item->damage_notes,
                ];
            }),
            "fees" => [
                "overdue" => $invoice->formatted_overdue_fee,
                "lost" => $invoice->formatted_lost_fee,
                "damage" => $invoice->formatted_damage_fee,
                "total" => $invoice->formatted_total_amount,
                "amount_paid" => $invoice->formatted_amount_paid,
                "amount_due" => $invoice->formatted_amount_due,
            ],
            "notes" => $invoice->notes,
        ];
    }
}

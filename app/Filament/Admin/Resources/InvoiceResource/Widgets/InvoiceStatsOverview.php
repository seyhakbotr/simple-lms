<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Widgets;

use App\Models\Invoice;
use App\Services\FeeCalculator;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $feeCalculator = app(FeeCalculator::class);

        // Total outstanding balance (unpaid + partially paid)
        $totalOutstandingInCents = Invoice::whereIn("status", [
            "unpaid",
            "partially_paid",
        ])->sum("amount_due");

        // Total collected (all paid amounts)
        $totalCollectedInCents = Invoice::sum("amount_paid");

        // Overdue invoices count
        $overdueCount = Invoice::whereIn("status", ["unpaid", "partially_paid"])
            ->where("due_date", "<", now())
            ->count();

        // Total unpaid invoices
        $unpaidCount = Invoice::whereIn("status", [
            "unpaid",
            "partially_paid",
        ])->count();

        // This month's invoices
        $thisMonthInvoices = Invoice::whereBetween("invoice_date", [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ])->count();

        // This month's revenue
        $thisMonthRevenueInCents = Invoice::whereBetween("invoice_date", [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ])->sum("amount_paid");

        return [
            Stat::make(
                "Total Outstanding",
                $feeCalculator->formatFine($totalOutstandingInCents / 100),
            )
                ->description("Amount due from unpaid invoices")
                ->descriptionIcon("heroicon-m-currency-dollar")
                ->color("danger")
                ->chart($this->getMonthlyOutstandingChart()),

            Stat::make(
                "Total Collected",
                $feeCalculator->formatFine($totalCollectedInCents / 100),
            )
                ->description("All-time payments received")
                ->descriptionIcon("heroicon-m-arrow-trending-up")
                ->color("success"),

            Stat::make("Overdue Invoices", $overdueCount)
                ->description($unpaidCount . " total unpaid")
                ->descriptionIcon("heroicon-m-exclamation-triangle")
                ->color($overdueCount > 0 ? "danger" : "success"),

            Stat::make("This Month", $thisMonthInvoices)
                ->description("Invoices generated")
                ->descriptionIcon("heroicon-m-document-text")
                ->color("primary"),

            Stat::make(
                "Month Revenue",
                $feeCalculator->formatFine($thisMonthRevenueInCents / 100),
            )
                ->description("Collected this month")
                ->descriptionIcon("heroicon-m-banknotes")
                ->color("success"),
        ];
    }

    /**
     * Get monthly outstanding balance chart data for last 6 months
     */
    protected function getMonthlyOutstandingChart(): array
    {
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->startOfMonth();

            $outstanding = Invoice::whereIn("status", [
                "unpaid",
                "partially_paid",
            ])
                ->where("invoice_date", "<=", $month->endOfMonth())
                ->sum("amount_due");

            $data[] = round($outstanding / 100, 2);
        }

        return $data;
    }
}

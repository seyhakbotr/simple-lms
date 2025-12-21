<?php

namespace App\Filament\Staff\Pages;

use App\Models\Invoice;
use Filament\Pages\Page;

class FinancialReports extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-banknotes";

    protected static string $view = "filament.staff.pages.financial-reports";

    protected static ?string $slug = "reports/financial";

    protected static ?string $navigationGroup = "Reports";

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = "Financial Reports";

    public function getTitle(): string
    {
        return "Financial Reports";
    }

    public function getInvoices()
    {
        return Invoice::query()
            ->with("user", "transaction")
            ->orderBy("created_at", "desc")
            ->get();
    }

    public function getRevenueBreakdown()
    {
        return [
            [
                "period" => "This Month",
                "overdue_fees" => Invoice::whereMonth("created_at", now()->month)->sum("overdue_fee"),
                "lost_fees" => Invoice::whereMonth("created_at", now()->month)->sum("lost_fee"),
                "damage_fees" => Invoice::whereMonth("created_at", now()->month)->sum("damage_fee"),
                "total_collected" => Invoice::whereMonth("created_at", now()->month)->sum("amount_paid"),
            ],
            [
                "period" => "Last Month",
                "overdue_fees" => Invoice::whereMonth("created_at", now()->subMonth()->month)->sum("overdue_fee"),
                "lost_fees" => Invoice::whereMonth("created_at", now()->subMonth()->month)->sum("lost_fee"),
                "damage_fees" => Invoice::whereMonth("created_at", now()->subMonth()->month)->sum("damage_fee"),
                "total_collected" => Invoice::whereMonth("created_at", now()->subMonth()->month)->sum("amount_paid"),
            ],
        ];
    }
}

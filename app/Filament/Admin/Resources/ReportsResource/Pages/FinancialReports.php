<?php

namespace App\Filament\Admin\Resources\ReportsResource\Pages;

use App\Filament\Admin\Resources\ReportsResource;
use App\Models\Invoice;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FinancialReports extends Page
{
    protected static string $resource = ReportsResource::class;

    protected static string $view = "filament.admin.resources.reports-resource.pages.financial-reports";

    public function getTitle(): string
    {
        return "Financial Reports";
    }

    public function mount(): void
    {
        // Page setup
    }

    public function getTable(string $tableId): ?Table
    {
        return match ($tableId) {
            "invoice-summary" => $this->invoiceSummaryTable(),
            "revenue-breakdown" => $this->revenueBreakdownTable(),
            default => null,
        };
    }

    protected function invoiceSummaryTable(): Table
    {
        return Table::make()
            ->query(
                Invoice::query()
                    ->with("user", "transaction")
                    ->orderBy("created_at", "desc"),
            )
            ->columns([
                Tables\Columns\TextColumn::make("invoice_number")
                    ->label("Invoice #")
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make("user.name")
                    ->label("Borrower")
                    ->searchable(),

                Tables\Columns\TextColumn::make("status")->badge()->color(
                    fn($state) => match ($state) {
                        "paid" => "success",
                        "partially_paid" => "warning",
                        "unpaid" => "danger",
                        "waived" => "secondary",
                        default => "gray",
                    },
                ),

                Tables\Columns\TextColumn::make("total_amount")
                    ->label("Total Amount")
                    ->money("USD")
                    ->sortable(),

                Tables\Columns\TextColumn::make("amount_paid")
                    ->label("Amount Paid")
                    ->money("USD")
                    ->sortable(),

                Tables\Columns\TextColumn::make("amount_due")
                    ->label("Amount Due")
                    ->money("USD")
                    ->sortable()
                    ->color(fn($state) => $state > 0 ? "danger" : "success"),

                Tables\Columns\TextColumn::make("invoice_date")
                    ->label("Invoice Date")
                    ->date("M d, Y")
                    ->sortable(),

                Tables\Columns\TextColumn::make("paid_at")
                    ->label("Paid At")
                    ->dateTime("M d, Y H:i")
                    ->placeholder("Not paid"),
            ])
            ->defaultSort("created_at", "desc")
            ->paginated([10, 25, 50]);
    }

    protected function revenueBreakdownTable(): Table
    {
        // This would show aggregated data - for now, return empty table
        return Table::make()
            ->query(
                collect([
                    [
                        "period" => "This Month",
                        "overdue_fees" => Invoice::whereMonth(
                            "created_at",
                            now()->month,
                        )->sum("overdue_fee"),
                        "lost_fees" => Invoice::whereMonth(
                            "created_at",
                            now()->month,
                        )->sum("lost_fee"),
                        "damage_fees" => Invoice::whereMonth(
                            "created_at",
                            now()->month,
                        )->sum("damage_fee"),
                        "total_collected" => Invoice::whereMonth(
                            "created_at",
                            now()->month,
                        )->sum("amount_paid"),
                    ],
                    [
                        "period" => "Last Month",
                        "overdue_fees" => Invoice::whereMonth(
                            "created_at",
                            now()->subMonth()->month,
                        )->sum("overdue_fee"),
                        "lost_fees" => Invoice::whereMonth(
                            "created_at",
                            now()->subMonth()->month,
                        )->sum("lost_fee"),
                        "damage_fees" => Invoice::whereMonth(
                            "created_at",
                            now()->subMonth()->month,
                        )->sum("damage_fee"),
                        "total_collected" => Invoice::whereMonth(
                            "created_at",
                            now()->subMonth()->month,
                        )->sum("amount_paid"),
                    ],
                ]),
            )
            ->columns([
                Tables\Columns\TextColumn::make("period")->label("Period"),

                Tables\Columns\TextColumn::make("overdue_fees")
                    ->label("Overdue Fees")
                    ->money("USD")
                    ->color("warning"),

                Tables\Columns\TextColumn::make("lost_fees")
                    ->label("Lost Fees")
                    ->money("USD")
                    ->color("danger"),

                Tables\Columns\TextColumn::make("damage_fees")
                    ->label("Damage Fees")
                    ->money("USD")
                    ->color("orange"),

                Tables\Columns\TextColumn::make("total_collected")
                    ->label("Total Collected")
                    ->money("USD")
                    ->color("success"),
            ])
            ->paginated(false);
    }
}

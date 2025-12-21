<?php

namespace App\Filament\Staff\Pages;

use App\Models\Transaction;
use Filament\Pages\Page;

class TransactionReports extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-document-text";

    protected static string $view = "filament.staff.pages.transaction-reports";

    protected static ?string $slug = "reports/transactions";

    protected static ?string $navigationGroup = "Reports";

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = "Transaction Details";

    public function getTitle(): string
    {
        return "Transaction Details Reports";
    }

    public function getAllTransactions()
    {
        return Transaction::query()
            ->with(["user", "items.book"])
            ->orderBy("created_at", "desc")
            ->get();
    }

    public function getRecentActivity()
    {
        return Transaction::query()
            ->with(["user", "items.book"])
            ->where("created_at", ">=", now()->subDays(30))
            ->orderBy("created_at", "desc")
            ->get();
    }

    public function getOverdueTransactions()
    {
        return Transaction::query()
            ->whereNull("returned_date")
            ->where("due_date", "<", now())
            ->with(["user", "items.book"])
            ->orderBy("due_date", "asc")
            ->get();
    }
}

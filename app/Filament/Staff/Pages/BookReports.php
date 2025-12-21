<?php

namespace App\Filament\Staff\Pages;

use App\Models\Book;
use App\Models\Transaction;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class BookReports extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-book-open";

    protected static string $view = "filament.staff.pages.book-reports";

    protected static ?string $slug = "reports/book-circulation";

    protected static ?string $navigationGroup = "Reports";

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = "Book Circulation";

    public function getTitle(): string
    {
        return "Book Circulation Reports";
    }

    public function getMostBorrowedBooks()
    {
        return Book::query()
            ->withCount([
                "transactionItems" => function (Builder $query) {
                    $query->whereHas("transaction", function (Builder $subQuery) {
                        $subQuery->whereNotNull("returned_date");
                    });
                },
            ])
            ->having("transaction_items_count", ">", 0)
            ->orderBy("transaction_items_count", "desc")
            ->limit(20)
            ->with('author')
            ->get();
    }

    public function getOverdueBooks()
    {
        return Transaction::query()
            ->whereNull("returned_date")
            ->where("due_date", "<", now())
            ->with(["user", "items.book"])
            ->orderBy("due_date", "asc")
            ->get();
    }

    public function getLostDamagedBooks()
    {
        return \App\Models\TransactionItem::query()
            ->whereIn("item_status", ["lost", "damaged"])
            ->with(["transaction.user", "book"])
            ->orderBy("updated_at", "desc")
            ->get();
    }
}

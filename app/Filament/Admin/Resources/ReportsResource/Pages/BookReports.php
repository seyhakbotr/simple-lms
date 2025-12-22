<?php

namespace App\Filament\Admin\Resources\ReportsResource\Pages;

use App\Filament\Admin\Resources\ReportsResource;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Transaction;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class BookReports extends Page
{
    protected static string $resource = ReportsResource::class;

    protected static string $view = "filament.admin.resources.reports-resource.pages.book-reports";

    public function getTitle(): string
    {
        return "Book Circulation Reports";
    }

    public function mount(): void
    {
        // Page setup
    }

    public function getTable(string $tableId): ?Table
    {
        return match ($tableId) {
            "most-borrowed" => $this->mostBorrowedBooksTable(),
            "overdue-books" => $this->overdueBooksTable(),
            "lost-damaged" => $this->lostDamagedBooksTable(),
            default => null,
        };
    }

    protected function mostBorrowedBooksTable(): Table
    {
        return Table::make()
            ->query(
                Book::query()
                    ->withCount([
                        "transactionItems" => function (Builder $query) {
                            $query->whereHas("transaction", function (
                                Builder $subQuery,
                            ) {
                                $subQuery->whereNotNull("returned_date");
                            });
                        },
                    ])
                    ->having("transaction_items_count", ">", 0)
                    ->orderBy("transaction_items_count", "desc")
                    ->limit(20),
            )
            ->columns([
                Tables\Columns\TextColumn::make("title")
                    ->label("Book Title")
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make("isbn")
                    ->label("ISBN")
                    ->searchable(),

                Tables\Columns\TextColumn::make("author.name")
                    ->label("Author")
                    ->searchable(),

                Tables\Columns\TextColumn::make("transaction_items_count")
                    ->label("Times Borrowed")
                    ->badge()
                    ->color("success"),

                Tables\Columns\TextColumn::make("stock")
                    ->label("Current Stock")
                    ->badge()
                    ->color(fn($state) => $state > 0 ? "info" : "danger"),
            ])
            ->filters([
                SelectFilter::make('genre')
                    ->relationship('genre', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Filter by Genre'),
            ])
            ->defaultSort("transaction_items_count", "desc")
            ->paginated(false);
    }

    protected function overdueBooksTable(): Table
    {
        return Table::make()
            ->query(
                Transaction::query()
                    ->whereNull("returned_date")
                    ->where("due_date", "<", now())
                    ->with(["user", "items.book"])
                    ->orderBy("due_date", "asc"),
            )
            ->columns([
                Tables\Columns\TextColumn::make("reference_no")
                    ->label("Transaction #")
                    ->searchable(),

                Tables\Columns\TextColumn::make("user.name")
                    ->label("Borrower")
                    ->searchable(),

                Tables\Columns\TextColumn::make("items.book.title")
                    ->label("Books")
                    ->listWithLineBreaks()
                    ->limitList(2),

                Tables\Columns\TextColumn::make("due_date")
                    ->label("Due Date")
                    ->date("M d, Y")
                    ->color("danger"),

                Tables\Columns\TextColumn::make("days_overdue")
                    ->label("Days Overdue")
                    ->getStateUsing(
                        fn($record) => $record->getDaysOverdue(),
                    )
                    ->badge()
                    ->color("danger"),

                Tables\Columns\TextColumn::make("total_fine")
                    ->label("Total Fine")
                    ->getStateUsing(
                        fn($record) => $record->formatted_total_fine,
                    )
                    ->color("warning"),
            ])
            ->defaultSort("due_date", "asc")
            ->paginated([10, 25, 50]);
    }

    protected function lostDamagedBooksTable(): Table
    {
        return Table::make()
            ->query(
                \App\Models\TransactionItem::query()
                    ->whereIn("item_status", ["lost", "damaged"])
                    ->with(["transaction.user", "book"])
                    ->orderBy("updated_at", "desc"),
            )
            ->columns([
                Tables\Columns\TextColumn::make("book.title")
                    ->label("Book Title")
                    ->searchable(),

                Tables\Columns\TextColumn::make("book.isbn")
                    ->label("ISBN"),

                Tables\Columns\TextColumn::make("transaction.user.name")
                    ->label("Borrower"),

                Tables\Columns\TextColumn::make("item_status")
                    ->label("Status")
                    ->badge()
                    ->color(
                        fn($state) => match ($state) {
                            "lost" => "danger",
                            "damaged" => "warning",
                            default => "gray",
                        },
                    ),

                Tables\Columns\TextColumn::make("damage_notes")
                    ->label("Notes")
                    ->limit(50),

                Tables\Columns\TextColumn::make("updated_at")
                    ->label("Reported")
                    ->dateTime("M d, Y H:i"),
            ])
            ->defaultSort("updated_at", "desc")
            ->paginated([10, 25, 50]);
    }
}

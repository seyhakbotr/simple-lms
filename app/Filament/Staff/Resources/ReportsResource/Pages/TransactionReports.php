<?php

namespace App\Filament\Staff\Resources\ReportsResource\Pages;

use App\Filament\Staff\Resources\ReportsResource;
use App\Models\Transaction;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionReports extends Page
{
    protected static string $resource = ReportsResource::class;

    protected static string $view = "filament.admin.resources.reports-resource.pages.transaction-reports";

    public function getTitle(): string
    {
        return "Transaction Details Reports";
    }

    public function mount(): void
    {
        // Page setup
    }

    public function getTable(string $tableId): ?Table
    {
        return match ($tableId) {
            "all-transactions" => $this->allTransactionsTable(),
            "recent-activity" => $this->recentActivityTable(),
            "overdue-transactions" => $this->overdueTransactionsTable(),
            default => null,
        };
    }

    protected function allTransactionsTable(): Table
    {
        return Table::make()
            ->query(
                Transaction::query()
                    ->with(["user", "items.book"])
                    ->orderBy("created_at", "desc"),
            )
            ->columns([
                Tables\Columns\TextColumn::make("reference_no")
                    ->label("Reference #")
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make("user.name")
                    ->label("Borrower")
                    ->searchable(),

                Tables\Columns\TextColumn::make("items_count")
                    ->counts("items")
                    ->label("Books")
                    ->badge()
                    ->color("info"),

                Tables\Columns\TextColumn::make("items.book.title")
                    ->label("Book Titles")
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList(),

                Tables\Columns\TextColumn::make("borrowed_date")
                    ->label("Borrowed")
                    ->date("M d, Y"),

                Tables\Columns\TextColumn::make("due_date")
                    ->label("Due Date")
                    ->date("M d, Y")
                    ->color(function ($record) {
                        if ($record->returned_date) {
                            return "success";
                        }
                        return $record->isOverdue() ? "danger" : null;
                    }),

                Tables\Columns\TextColumn::make("returned_date")
                    ->label("Returned")
                    ->date("M d, Y")
                    ->placeholder("Not returned"),

                Tables\Columns\TextColumn::make("lifecycle_status")
                    ->label("Status")
                    ->badge(),

                Tables\Columns\TextColumn::make("total_fine")
                    ->label("Total Fine")
                    ->getStateUsing(
                        fn($record) => $record->formatted_total_fine,
                    )
                    ->placeholder("$0.00"),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("lifecycle_status")
                    ->options([
                        "active" => "Active",
                        "completed" => "Completed",
                        "cancelled" => "Cancelled",
                        "archived" => "Archived",
                    ]),
                Tables\Filters\Filter::make("overdue")
                    ->label("Overdue Only")
                    ->query(function (Builder $query) {
                        return $query
                            ->whereNull("returned_date")
                            ->where("due_date", "<", now());
                    }),
                Tables\Filters\Filter::make("returned")
                    ->label("Returned Only")
                    ->query(function (Builder $query) {
                        return $query->whereNotNull("returned_date");
                    }),
            ])
            ->defaultSort("created_at", "desc")
            ->paginated([10, 25, 50]);
    }

    protected function recentActivityTable(): Table
    {
        return Table::make()
            ->query(
                Transaction::query()
                    ->with(["user", "items.book"])
                    ->where("created_at", ">=", now()->subDays(30))
                    ->orderBy("created_at", "desc"),
            )
            ->columns([
                Tables\Columns\TextColumn::make("reference_no")
                    ->label("Reference #")
                    ->searchable(),

                Tables\Columns\TextColumn::make("user.name")
                    ->label("Borrower"),

                Tables\Columns\TextColumn::make("items.book.title")
                    ->label("Books")
                    ->listWithLineBreaks()
                    ->limitList(2),

                Tables\Columns\TextColumn::make("borrowed_date")
                    ->label("Borrowed")
                    ->dateTime("M d, Y H:i"),

                Tables\Columns\TextColumn::make("lifecycle_status")
                    ->label("Status")
                    ->badge(),

                Tables\Columns\TextColumn::make("returned_date")
                    ->label("Returned")
                    ->dateTime("M d, Y H:i")
                    ->placeholder("Not returned"),
            ])
            ->defaultSort("created_at", "desc")
            ->paginated([10, 25, 50]);
    }

    protected function overdueTransactionsTable(): Table
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
                    ->label("Reference #")
                    ->searchable(),

                Tables\Columns\TextColumn::make("user.name")
                    ->label("Borrower"),

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

                Tables\Columns\TextColumn::make("items.book.title")
                    ->label("Books")
                    ->listWithLineBreaks()
                    ->limitList(2),

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
}

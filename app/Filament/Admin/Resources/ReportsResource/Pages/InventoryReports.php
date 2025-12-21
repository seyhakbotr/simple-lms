<?php

namespace App\Filament\Admin\Resources\ReportsResource\Pages;

use App\Filament\Admin\Resources\ReportsResource;
use App\Models\Book;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryReports extends Page
{
    protected static string $resource = ReportsResource::class;

    protected static string $view = "filament.admin.resources.reports-resource.pages.inventory-reports";

    public function getTitle(): string
    {
        return "Inventory Status Reports";
    }

    public function mount(): void
    {
        // Page setup
    }

    public function getTable(string $tableId): ?Table
    {
        return match ($tableId) {
            "book-availability" => $this->bookAvailabilityTable(),
            "low-stock" => $this->lowStockTable(),
            "out-of-stock" => $this->outOfStockTable(),
            default => null,
        };
    }

    protected function bookAvailabilityTable(): Table
    {
        return Table::make()
            ->query(
                Book::query()
                    ->with("author", "genre", "publisher")
                    ->orderBy("stock", "desc"),
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

                Tables\Columns\TextColumn::make("genre.name")
                    ->label("Genre"),

                Tables\Columns\TextColumn::make("stock")
                    ->label("Stock")
                    ->badge()
                    ->color(function ($state) {
                        if ($state == 0) {
                            return "danger";
                        }
                        if ($state <= 5) {
                            return "warning";
                        }
                        return "success";
                    }),

                Tables\Columns\TextColumn::make("available")
                    ->label("Available")
                    ->badge()
                    ->color(fn($state) => $state ? "success" : "danger")
                    ->formatStateUsing(fn($state) => $state ? "Yes" : "No"),

                Tables\Columns\TextColumn::make("created_at")
                    ->label("Added")
                    ->date("M d, Y"),
            ])
            ->defaultSort("stock", "desc")
            ->paginated([10, 25, 50]);
    }

    protected function lowStockTable(): Table
    {
        return Table::make()
            ->query(
                Book::query()
                    ->where("stock", ">", 0)
                    ->where("stock", "<=", 5)
                    ->with("author", "genre")
                    ->orderBy("stock", "asc"),
            )
            ->columns([
                Tables\Columns\TextColumn::make("title")
                    ->label("Book Title")
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make("isbn")
                    ->label("ISBN"),

                Tables\Columns\TextColumn::make("author.name")
                    ->label("Author"),

                Tables\Columns\TextColumn::make("stock")
                    ->label("Stock")
                    ->badge()
                    ->color("warning"),

                Tables\Columns\TextColumn::make("genre.name")
                    ->label("Genre"),
            ])
            ->defaultSort("stock", "asc")
            ->paginated(false);
    }

    protected function outOfStockTable(): Table
    {
        return Table::make()
            ->query(
                Book::query()
                    ->where("stock", 0)
                    ->with("author", "genre", "publisher")
                    ->orderBy("updated_at", "desc"),
            )
            ->columns([
                Tables\Columns\TextColumn::make("title")
                    ->label("Book Title")
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make("isbn")
                    ->label("ISBN"),

                Tables\Columns\TextColumn::make("author.name")
                    ->label("Author"),

                Tables\Columns\TextColumn::make("genre.name")
                    ->label("Genre"),

                Tables\Columns\TextColumn::make("publisher.name")
                    ->label("Publisher"),

                Tables\Columns\TextColumn::make("updated_at")
                    ->label("Last Updated")
                    ->date("M d, Y"),
            ])
            ->defaultSort("updated_at", "desc")
            ->paginated([10, 25, 50]);
    }
}

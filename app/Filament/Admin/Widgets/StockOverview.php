<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Book;
use App\Models\StockTransaction;
use App\Models\StockTransactionItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $totalBooks = Book::count();
        $totalStock = Book::sum("stock");
        $lowStockBooks = Book::where("stock", "<", 10)->count();
        $recentAdjustments = StockTransaction::whereBetween("created_at", [
            now()->subDays(7),
            now(),
        ])->count();
        $recentItemsAdjusted = StockTransactionItem::whereBetween(
            "created_at",
            [now()->subDays(7), now()],
        )->count();

        return [
            Stat::make("Total Books", $totalBooks)
                ->description("Total book titles")
                ->descriptionIcon("heroicon-m-book-open")
                ->color("primary"),

            Stat::make("Total Stock", number_format($totalStock))
                ->description("Total books in stock")
                ->descriptionIcon("heroicon-m-archive-box")
                ->color("success"),

            Stat::make("Low Stock Books", $lowStockBooks)
                ->description("Books with less than 10 copies")
                ->descriptionIcon("heroicon-m-exclamation-triangle")
                ->color($lowStockBooks > 0 ? "warning" : "success"),

            Stat::make("Recent Transactions", $recentAdjustments)
                ->description(
                    $recentItemsAdjusted . " books adjusted in last 7 days",
                )
                ->descriptionIcon("heroicon-m-arrow-path")
                ->color("info"),
        ];
    }
}

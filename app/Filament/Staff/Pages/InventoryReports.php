<?php

namespace App\Filament\Staff\Pages;

use App\Models\Book;
use Filament\Pages\Page;

class InventoryReports extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-archive-box";

    protected static string $view = "filament.staff.pages.inventory-reports";

    protected static ?string $slug = "reports/inventory";

    protected static ?string $navigationGroup = "Reports";

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = "Inventory Status";

    public function getTitle(): string
    {
        return "Inventory Status Reports";
    }

    public function getBookAvailability()
    {
        return Book::query()
            ->with("author", "genre", "publisher")
            ->orderBy("stock", "desc")
            ->get();
    }

    public function getLowStock()
    {
        return Book::query()
            ->where("stock", ">", 0)
            ->where("stock", "<=", 5)
            ->with("author", "genre")
            ->orderBy("stock", "asc")
            ->get();
    }

    public function getOutOfStock()
    {
        return Book::query()
            ->where("stock", 0)
            ->with("author", "genre", "publisher")
            ->orderBy("updated_at", "desc")
            ->get();
    }
}

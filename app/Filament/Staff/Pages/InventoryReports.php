<?php

namespace App\Filament\Staff\Pages;

use App\Models\Book;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class InventoryReports extends Page
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = "heroicon-o-archive-box";

    protected static string $view = "filament.staff.pages.inventory-reports";

    protected static ?string $slug = "reports/inventory";

    protected static ?string $navigationGroup = "Reports";

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = "Inventory Status";

    public ?string $start_date = null;
    public ?string $end_date = null;

    public function getTitle(): string
    {
        return "Inventory Status Reports";
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make("start_date")
                    ->label("Start Date")
                    ->placeholder("Select start date"),

                DatePicker::make("end_date")
                    ->label("End Date")
                    ->placeholder("Select end date"),
            ])
            ->columns(2);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make("apply_filters")
                ->label("Apply Filters")
                ->icon("heroicon-o-funnel")
                ->action(fn() => $this->applyFilters()),

            Action::make("print")
                ->label("Print Report")
                ->icon("heroicon-o-printer")
                ->action(fn() => $this->printReport()),

            Action::make("export_csv")
                ->label("Export to CSV")
                ->icon("heroicon-o-arrow-down-tray")
                ->action(fn() => $this->exportToCsv()),
        ];
    }

    public function getBookAvailability(): Collection
    {
        $query = Book::query()
            ->with("author", "genre", "publisher")
            ->orderBy("stock", "desc");

        if ($this->start_date) {
            $query->whereDate("created_at", ">=", $this->start_date);
        }

        if ($this->end_date) {
            $query->whereDate("created_at", "<=", $this->end_date);
        }

        return $query->get();
    }

    public function getLowStock(): Collection
    {
        $query = Book::query()
            ->where("stock", ">", 0)
            ->where("stock", "<=", 5)
            ->with("author", "genre")
            ->orderBy("stock", "asc");

        if ($this->start_date) {
            $query->whereDate("created_at", ">=", $this->start_date);
        }

        if ($this->end_date) {
            $query->whereDate("created_at", "<=", $this->end_date);
        }

        return $query->get();
    }

    public function getOutOfStock(): Collection
    {
        $query = Book::query()
            ->where("stock", 0)
            ->with("author", "genre", "publisher")
            ->orderBy("updated_at", "desc");

        if ($this->start_date) {
            $query->whereDate("created_at", ">=", $this->start_date);
        }

        if ($this->end_date) {
            $query->whereDate("created_at", "<=", $this->end_date);
        }

        return $query->get();
    }

    public function applyFilters()
    {
        // Refresh the data when filters are applied
        $this->dispatch('$refresh');
    }

    public function printReport()
    {
        // Trigger print dialog
        $this->dispatch("print-report");
    }

    public function exportToCsv()
    {
        $books = $this->getBookAvailability();

        $filename =
            "inventory_reports_" . now()->format("Y-m-d_H-i-s") . ".csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($books) {
            $file = fopen("php://output", "w");

            // CSV headers
            fputcsv($file, [
                "Title",
                "Author",
                "ISBN",
                "Stock",
                "Genre",
                "Publisher",
            ]);

            // CSV data
            foreach ($books as $book) {
                fputcsv($file, [
                    $book->title,
                    $book->author?->name ?? "Unknown",
                    $book->isbn,
                    $book->stock,
                    $book->genre?->name ?? "N/A",
                    $book->publisher?->name ?? "N/A",
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

<?php

namespace App\Filament\Staff\Pages;

use App\Models\Book;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BookReports extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = "heroicon-o-book-open";

    protected static string $view = "filament.staff.pages.book-reports";

    protected static ?string $slug = "reports/book-circulation";

    protected static ?string $navigationGroup = "Reports";

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = "Book Circulation";

    public ?string $start_date = null;
    public ?string $end_date = null;

    public function getTitle(): string
    {
        return "Book Circulation Reports";
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

    public function getMostBorrowedBooks(): Collection
    {
        $query = Book::query()
            ->withCount([
                "transactionItems" => function (Builder $query) {
                    $query->whereHas("transaction", function (
                        Builder $subQuery,
                    ) {
                        $subQuery->whereNotNull("returned_date");
                        if ($this->start_date) {
                            $subQuery->whereDate(
                                "created_at",
                                ">=",
                                $this->start_date,
                            );
                        }
                        if ($this->end_date) {
                            $subQuery->whereDate(
                                "created_at",
                                "<=",
                                $this->end_date,
                            );
                        }
                    });
                },
            ])
            ->having("transaction_items_count", ">", 0)
            ->orderBy("transaction_items_count", "desc")
            ->limit(20)
            ->with("author");

        return $query->get();
    }

    public function getOverdueBooks(): Collection
    {
        $query = Transaction::query()
            ->whereNull("returned_date")
            ->where("due_date", "<", now())
            ->with(["user", "items.book"])
            ->orderBy("due_date", "asc");

        if ($this->start_date) {
            $query->whereDate("created_at", ">=", $this->start_date);
        }
        if ($this->end_date) {
            $query->whereDate("created_at", "<=", $this->end_date);
        }

        return $query->get();
    }

    public function getLostDamagedBooks(): Collection
    {
        $query = \App\Models\TransactionItem::query()
            ->whereIn("item_status", ["lost", "damaged"])
            ->with(["transaction.user", "book"])
            ->orderBy("updated_at", "desc");

        if ($this->start_date) {
            $query->whereHas("transaction", function (Builder $subQuery) {
                $subQuery->whereDate("created_at", ">=", $this->start_date);
            });
        }
        if ($this->end_date) {
            $query->whereHas("transaction", function (Builder $subQuery) {
                $subQuery->whereDate("created_at", "<=", $this->end_date);
            });
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
        $books = $this->getMostBorrowedBooks();

        $filename = "book_reports_" . now()->format("Y-m-d_H-i-s") . ".csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($books) {
            $file = fopen("php://output", "w");

            // CSV headers
            fputcsv($file, ["Title", "Author", "Borrow Count"]);

            // CSV data
            foreach ($books as $book) {
                fputcsv($file, [
                    $book->title,
                    $book->author?->name ?? "Unknown",
                    $book->transaction_items_count,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

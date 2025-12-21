<?php

namespace App\Filament\Staff\Pages;

use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class TransactionReports extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = "heroicon-o-document-text";

    protected static string $view = "filament.staff.pages.transaction-reports";

    protected static ?string $slug = "reports/transactions";

    protected static ?string $navigationGroup = "Reports";

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = "Transaction Details";

    public ?string $start_date = null;
    public ?string $end_date = null;

    public function getTitle(): string
    {
        return "Transaction Details Reports";
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

    public function getAllTransactions(): Collection
    {
        $query = Transaction::query()
            ->with(["user", "items.book"])
            ->orderBy("created_at", "desc");

        if ($this->start_date) {
            $query->whereDate("created_at", ">=", $this->start_date);
        }

        if ($this->end_date) {
            $query->whereDate("created_at", "<=", $this->end_date);
        }

        return $query->get();
    }

    public function getRecentActivity(): Collection
    {
        $query = Transaction::query()
            ->with(["user", "items.book"])
            ->where("created_at", ">=", now()->subDays(30))
            ->orderBy("created_at", "desc");

        if ($this->start_date) {
            $query->whereDate("created_at", ">=", $this->start_date);
        }

        if ($this->end_date) {
            $query->whereDate("created_at", "<=", $this->end_date);
        }

        return $query->get();
    }

    public function getOverdueTransactions(): Collection
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
        $transactions = $this->getAllTransactions();

        $filename =
            "transaction_reports_" . now()->format("Y-m-d_H-i-s") . ".csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen("php://output", "w");

            // CSV headers
            fputcsv($file, [
                "Reference #",
                "Borrower",
                "Books",
                "Borrowed Date",
                "Due Date",
                "Returned Date",
                "Status",
                "Total Fine",
            ]);

            // CSV data
            foreach ($transactions as $transaction) {
                $books = $transaction->items->pluck("book.title")->join(", ");

                fputcsv($file, [
                    $transaction->reference_no,
                    $transaction->user->name,
                    $books,
                    $transaction->borrowed_date->format("Y-m-d H:i:s"),
                    $transaction->due_date->format("Y-m-d"),
                    $transaction->returned_date
                        ? $transaction->returned_date->format("Y-m-d H:i:s")
                        : "Not returned",
                    $transaction->lifecycle_status?->getLabel() ?? "N/A",
                    $transaction->formatted_total_fine ?: '$0.00',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

<?php

namespace App\Filament\Staff\Pages;

use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class FinancialReports extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = "heroicon-o-banknotes";

    protected static string $view = "filament.staff.pages.financial-reports";

    protected static ?string $slug = "reports/financial";

    protected static ?string $navigationGroup = "Reports";

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = "Financial Reports";

    public ?string $start_date = null;
    public ?string $end_date = null;

    public function getTitle(): string
    {
        return "Financial Reports";
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

    public function getInvoices(): Collection
    {
        $query = Invoice::query()
            ->with("user", "transaction")
            ->orderBy("created_at", "desc");

        if ($this->start_date) {
            $query->whereDate("created_at", ">=", $this->start_date);
        }

        if ($this->end_date) {
            $query->whereDate("created_at", "<=", $this->end_date);
        }

        return $query->get();
    }

    public function getRevenueBreakdown()
    {
        $query = Invoice::query();

        if ($this->start_date) {
            $query->whereDate("created_at", ">=", $this->start_date);
        }

        if ($this->end_date) {
            $query->whereDate("created_at", "<=", $this->end_date);
        }

        return [
            [
                "period" => "Filtered Period",
                "overdue_fees" => (clone $query)->sum("overdue_fee"),
                "lost_fees" => (clone $query)->sum("lost_fee"),
                "damage_fees" => (clone $query)->sum("damage_fee"),
                "total_collected" => (clone $query)->sum("amount_paid"),
            ],
        ];
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
        $invoices = $this->getInvoices();

        $filename =
            "financial_reports_" . now()->format("Y-m-d_H-i-s") . ".csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($invoices) {
            $file = fopen("php://output", "w");

            // CSV headers
            fputcsv($file, [
                "Invoice Number",
                "User",
                "Transaction Reference",
                "Overdue Fee",
                "Lost Fee",
                "Damage Fee",
                "Total Amount",
                "Amount Paid",
                "Created At",
            ]);

            // CSV data
            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->invoice_number,
                    $invoice->user->name ?? "N/A",
                    $invoice->transaction->reference_no ?? "N/A",
                    $invoice->overdue_fee,
                    $invoice->lost_fee,
                    $invoice->damage_fee,
                    $invoice->total_amount,
                    $invoice->amount_paid,
                    $invoice->created_at->format("Y-m-d H:i:s"),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

<?php

namespace App\Filament\Staff\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class MemberReports extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = "heroicon-o-users";

    protected static string $view = "filament.staff.pages.member-reports";

    protected static ?string $slug = "reports/members";

    protected static ?string $navigationGroup = "Reports";

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = "Member Statistics";

    public ?string $start_date = null;
    public ?string $end_date = null;

    public function getTitle(): string
    {
        return "Member Statistics Reports";
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

    public function getActiveMembers(): Collection
    {
        $query = User::query()
            ->where("status", true)
            ->where("role_id", 3)
            ->with("membershipType", "transactions")
            ->withCount([
                "transactions" => function ($query) {
                    $query->whereNotNull("returned_date");
                    if ($this->start_date) {
                        $query->whereDate(
                            "created_at",
                            ">=",
                            $this->start_date,
                        );
                    }
                    if ($this->end_date) {
                        $query->whereDate("created_at", "<=", $this->end_date);
                    }
                },
                "transactions as active_transactions_count" => function (
                    $query,
                ) {
                    $query->whereNull("returned_date");
                    if ($this->start_date) {
                        $query->whereDate(
                            "created_at",
                            ">=",
                            $this->start_date,
                        );
                    }
                    if ($this->end_date) {
                        $query->whereDate("created_at", "<=", $this->end_date);
                    }
                },
            ])
            ->orderBy("created_at", "desc");

        if ($this->start_date) {
            $query->whereDate("created_at", ">=", $this->start_date);
        }
        if ($this->end_date) {
            $query->whereDate("created_at", "<=", $this->end_date);
        }

        return $query->get();
    }

    public function getMembershipTypes(): Collection
    {
        return \App\Models\MembershipType::query()
            ->withCount("users")
            ->orderBy("created_at", "desc")
            ->get();
    }

    public function getTopBorrowers(): Collection
    {
        $query = User::query()
            ->where("role_id", 3)
            ->with("membershipType")
            ->withCount([
                "transactions" => function ($query) {
                    $query->whereNotNull("returned_date");
                    if ($this->start_date) {
                        $query->whereDate(
                            "created_at",
                            ">=",
                            $this->start_date,
                        );
                    }
                    if ($this->end_date) {
                        $query->whereDate("created_at", "<=", $this->end_date);
                    }
                },
            ])
            ->having("transactions_count", ">", 0)
            ->orderBy("transactions_count", "desc")
            ->limit(20);

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
        $members = $this->getActiveMembers();

        $filename = "member_reports_" . now()->format("Y-m-d_H-i-s") . ".csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($members) {
            $file = fopen("php://output", "w");

            // CSV headers
            fputcsv($file, [
                "Name",
                "Email",
                "Membership Type",
                "Registration Date",
                "Total Transactions",
                "Active Transactions",
            ]);

            // CSV data
            foreach ($members as $member) {
                fputcsv($file, [
                    $member->name,
                    $member->email,
                    $member->membershipType->name ?? "N/A",
                    $member->created_at->format("Y-m-d"),
                    $member->transactions_count,
                    $member->active_transactions_count,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

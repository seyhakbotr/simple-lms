<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label("Create Invoice Manually")];
    }

    protected function getHeaderWidgets(): array
    {
        return [InvoiceResource\Widgets\InvoiceStatsOverview::class];
    }

    public function getTabs(): array
    {
        return [
            "all" => Tab::make("All Invoices")->badge(
                Invoice::query()->count(),
            ),

            "unpaid" => Tab::make("Unpaid")
                ->badge(Invoice::query()->where("status", "unpaid")->count())
                ->badgeColor("danger")
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where("status", "unpaid"),
                ),

            "partially_paid" => Tab::make("Partially Paid")
                ->badge(
                    Invoice::query()
                        ->where("status", "partially_paid")
                        ->count(),
                )
                ->badgeColor("warning")
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where(
                        "status",
                        "partially_paid",
                    ),
                ),

            "overdue" => Tab::make("Overdue")
                ->badge(
                    Invoice::query()
                        ->whereIn("status", ["unpaid", "partially_paid"])
                        ->where("due_date", "<", now())
                        ->count(),
                )
                ->badgeColor("danger")
                ->modifyQueryUsing(
                    fn(Builder $query) => $query
                        ->whereIn("status", ["unpaid", "partially_paid"])
                        ->where("due_date", "<", now()),
                ),

            "paid" => Tab::make("Paid")
                ->badge(Invoice::query()->where("status", "paid")->count())
                ->badgeColor("success")
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where("status", "paid"),
                ),

            "waived" => Tab::make("Waived")
                ->badge(Invoice::query()->where("status", "waived")->count())
                ->badgeColor("secondary")
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where("status", "waived"),
                ),
        ];
    }
}

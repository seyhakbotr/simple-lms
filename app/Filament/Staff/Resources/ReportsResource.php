<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ReportsResource\Pages;
use App\Models\Book;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportsResource extends Resource
{
    protected static ?string $model = null;

    protected static ?string $navigationIcon = "heroicon-o-chart-bar";

    protected static ?string $navigationGroup = "Reports";

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return "Reports";
    }

    public static function getNavigationBadge(): ?string
    {
        return "New";
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->label("Report Name")
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make("description")
                    ->label("Description")
                    ->wrap(),

                Tables\Columns\TextColumn::make("category")
                    ->label("Category")
                    ->badge()
                    ->color(
                        fn(string $state): string => match ($state) {
                            "Financial" => "success",
                            "Circulation" => "info",
                            "Inventory" => "warning",
                            "Members" => "primary",
                            default => "gray",
                        },
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("category")->options([
                    "Financial" => "Financial",
                    "Circulation" => "Circulation",
                    "Inventory" => "Inventory",
                    "Members" => "Members",
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make("view_report")
                    ->label("View Report")
                    ->icon("heroicon-o-eye")
                    ->url(
                        fn($record) => match ($record["slug"]) {
                            "book-circulation" => route(
                                "filament.staff.resources.reports.book-reports",
                            ),
                            "financial-summary" => route(
                                "filament.staff.resources.reports.financial-reports",
                            ),
                            "member-statistics" => route(
                                "filament.staff.resources.reports.member-reports",
                            ),
                            "inventory-status" => route(
                                "filament.staff.resources.reports.inventory-reports",
                            ),
                            "transaction-details" => route(
                                "filament.staff.resources.reports.transaction-reports",
                            ),
                            default => "#",
                        },
                    )
                    ->openUrlInNewTab(),
            ])
            ->defaultSort("category")
            ->paginated(false)
            ->query(function (Builder $query) {
                // Return static data for reports
                return collect([
                    [
                        "name" => "Book Circulation Report",
                        "description" =>
                            "View most borrowed books, overdue statistics, and circulation trends",
                        "category" => "Circulation",
                        "slug" => "book-circulation",
                    ],
                    [
                        "name" => "Financial Summary Report",
                        "description" =>
                            "Revenue analysis, payment collections, and fee breakdowns",
                        "category" => "Financial",
                        "slug" => "financial-summary",
                    ],
                    [
                        "name" => "Member Statistics Report",
                        "description" =>
                            "Membership trends, active users, and borrower analytics",
                        "category" => "Members",
                        "slug" => "member-statistics",
                    ],
                    [
                        "name" => "Inventory Status Report",
                        "description" =>
                            "Book availability, lost/damaged items, and stock levels",
                        "category" => "Inventory",
                        "slug" => "inventory-status",
                    ],
                    [
                        "name" => "Transaction Details Report",
                        "description" =>
                            "Detailed transaction logs with filtering and export options",
                        "category" => "Circulation",
                        "slug" => "transaction-details",
                    ],
                ]);
            });
    }

    public static function getRelations(): array
    {
        return [
                //
            ];
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListReports::route("/"),
            "book-reports" => Pages\BookReports::route("/book-reports"),
            "financial-reports" => Pages\FinancialReports::route(
                "/financial-reports",
            ),
            "member-reports" => Pages\MemberReports::route("/member-reports"),
            "inventory-reports" => Pages\InventoryReports::route(
                "/inventory-reports",
            ),
            "transaction-reports" => Pages\TransactionReports::route(
                "/transaction-reports",
            ),
        ];
    }
}

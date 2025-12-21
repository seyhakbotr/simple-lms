<?php

namespace App\Filament\Staff\Resources\ReportsResource\Pages;

use App\Filament\Staff\Resources\ReportsResource;
use App\Models\User;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;

class MemberReports extends Page
{
    protected static string $resource = ReportsResource::class;

    protected static string $view = "filament.admin.resources.reports-resource.pages.member-reports";

    public function getTitle(): string
    {
        return "Member Statistics Reports";
    }

    public function mount(): void
    {
        // Page setup
    }

    public function getTable(string $tableId): ?Table
    {
        return match ($tableId) {
            "active-members" => $this->activeMembersTable(),
            "membership-types" => $this->membershipTypesTable(),
            "top-borrowers" => $this->topBorrowersTable(),
            default => null,
        };
    }

    protected function activeMembersTable(): Table
    {
        return Table::make()
            ->query(
                User::query()
                    ->where("status", true)
                    ->where("role_id", 3) // borrower role
                    ->with("membershipType", "transactions")
                    ->withCount([
                        "transactions" => function ($query) {
                            $query->whereNotNull("returned_date");
                        },
                        "transactions as active_transactions_count" => function (
                            $query,
                        ) {
                            $query->whereNull("returned_date");
                        },
                    ])
                    ->orderBy("created_at", "desc"),
            )
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->label("Member Name")
                    ->searchable(),

                Tables\Columns\TextColumn::make("email")
                    ->label("Email")
                    ->searchable(),

                Tables\Columns\TextColumn::make("membershipType.name")
                    ->label("Membership Type"),

                Tables\Columns\TextColumn::make("transactions_count")
                    ->label("Total Borrowings")
                    ->badge()
                    ->color("info"),

                Tables\Columns\TextColumn::make("active_transactions_count")
                    ->label("Active Borrowings")
                    ->badge()
                    ->color(
                        fn($state) => $state > 0 ? "warning" : "success",
                    ),

                Tables\Columns\TextColumn::make("membership_expires_at")
                    ->label("Membership Expires")
                    ->date("M d, Y")
                    ->color(function ($record) {
                        if (!$record->membership_expires_at) {
                            return "danger";
                        }
                        return $record->membership_expires_at->isPast()
                            ? "danger"
                            : "success";
                    }),

                Tables\Columns\TextColumn::make("created_at")
                    ->label("Member Since")
                    ->date("M d, Y"),
            ])
            ->defaultSort("created_at", "desc")
            ->paginated([10, 25, 50]);
    }

    protected function membershipTypesTable(): Table
    {
        return Table::make()
            ->query(
                \App\Models\MembershipType::query()
                    ->withCount("users")
                    ->orderBy("created_at", "desc"),
            )
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->label("Membership Type")
                    ->searchable(),

                Tables\Columns\TextColumn::make("max_books_allowed")
                    ->label("Max Books"),

                Tables\Columns\TextColumn::make("max_borrow_days")
                    ->label("Max Borrow Days"),

                Tables\Columns\TextColumn::make("users_count")
                    ->label("Total Members")
                    ->badge()
                    ->color("primary"),

                Tables\Columns\TextColumn::make("fee_per_month")
                    ->label("Monthly Fee")
                    ->money("USD"),

                Tables\Columns\TextColumn::make("created_at")
                    ->label("Created")
                    ->date("M d, Y"),
            ])
            ->defaultSort("users_count", "desc")
            ->paginated(false);
    }

    protected function topBorrowersTable(): Table
    {
        return Table::make()
            ->query(
                User::query()
                    ->where("role_id", 3) // borrower role
                    ->with("membershipType")
                    ->withCount([
                        "transactions" => function ($query) {
                            $query->whereNotNull("returned_date");
                        },
                    ])
                    ->having("transactions_count", ">", 0)
                    ->orderBy("transactions_count", "desc")
                    ->limit(20),
            )
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->label("Borrower Name")
                    ->searchable(),

                Tables\Columns\TextColumn::make("email")
                    ->label("Email"),

                Tables\Columns\TextColumn::make("membershipType.name")
                    ->label("Membership Type"),

                Tables\Columns\TextColumn::make("transactions_count")
                    ->label("Books Borrowed")
                    ->badge()
                    ->color("success"),

                Tables\Columns\TextColumn::make("created_at")
                    ->label("Member Since")
                    ->date("M d, Y"),
            ])
            ->defaultSort("transactions_count", "desc")
            ->paginated(false);
    }
}

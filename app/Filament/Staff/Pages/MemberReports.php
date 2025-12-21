<?php

namespace App\Filament\Staff\Pages;

use App\Models\User;
use Filament\Pages\Page;

class MemberReports extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-users";

    protected static string $view = "filament.staff.pages.member-reports";

    protected static ?string $slug = "reports/members";

    protected static ?string $navigationGroup = "Reports";

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = "Member Statistics";

    public function getTitle(): string
    {
        return "Member Statistics Reports";
    }

    public function getActiveMembers()
    {
        return User::query()
            ->where("status", true)
            ->where("role_id", 3)
            ->with("membershipType", "transactions")
            ->withCount([
                "transactions" => function ($query) {
                    $query->whereNotNull("returned_date");
                },
                "transactions as active_transactions_count" => function ($query) {
                    $query->whereNull("returned_date");
                },
            ])
            ->orderBy("created_at", "desc")
            ->get();
    }

    public function getMembershipTypes()
    {
        return \App\Models\MembershipType::query()
            ->withCount("users")
            ->orderBy("created_at", "desc")
            ->get();
    }

    public function getTopBorrowers()
    {
        return User::query()
            ->where("role_id", 3)
            ->with("membershipType")
            ->withCount([
                "transactions" => function ($query) {
                    $query->whereNotNull("returned_date");
                },
            ])
            ->having("transactions_count", ">", 0)
            ->orderBy("transactions_count", "desc")
            ->limit(20)
            ->get();
    }
}

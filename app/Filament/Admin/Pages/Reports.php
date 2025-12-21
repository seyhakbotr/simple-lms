<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class Reports extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-chart-bar";

    protected static string $view = "filament.admin.pages.reports";

    protected static ?string $slug = "reports";

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = "Reports";

    public function getTitle(): string
    {
        return "Library Reports";
    }
}

<?php

namespace App\Filament\Staff\Resources\ReportsResource\Pages;

use App\Filament\Staff\Resources\ReportsResource;
use Filament\Resources\Pages\Page;

class ListReports extends Page
{
    protected static string $resource = ReportsResource::class;

    protected static string $view = "filament.staff.resources.reports-resource.pages.list-reports";

    public function getTitle(): string
    {
        return "Reports";
    }
}

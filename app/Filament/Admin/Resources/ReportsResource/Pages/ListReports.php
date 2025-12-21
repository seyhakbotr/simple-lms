<?php

namespace App\Filament\Admin\Resources\ReportsResource\Pages;

use App\Filament\Admin\Resources\ReportsResource;
use Filament\Resources\Pages\Page;

class ListReports extends Page
{
    protected static string $resource = ReportsResource::class;

    protected static string $view = "filament.admin.resources.reports-resource.pages.list-reports";

    public function getTitle(): string
    {
        return "Reports";
    }
}

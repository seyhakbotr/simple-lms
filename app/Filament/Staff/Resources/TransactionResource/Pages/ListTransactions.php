<?php

namespace App\Filament\Staff\Resources\TransactionResource\Pages;

use App\Enums\BorrowedStatus;
use App\Filament\Staff\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'All' => Tab::make(),
            'Active' => Tab::make('Active')
                ->modifyQueryUsing(fn ($query) => $query->active()),
            'Overdue' => Tab::make('Overdue')
                ->modifyQueryUsing(fn ($query) => $query->overdue()),
            'Completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn ($query) => $query->completed()),
            'Cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn ($query) => $query->where('lifecycle_status', LifecycleStatus::Cancelled)),
            'Archived' => Tab::make('Archived')
                ->modifyQueryUsing(fn ($query) => $query->where('lifecycle_status', LifecycleStatus::Archived)),
        ];
    }
}

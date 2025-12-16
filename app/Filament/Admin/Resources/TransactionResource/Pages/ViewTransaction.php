<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('return')
                ->label('Return Books')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->url(fn() => TransactionResource::getUrl('return', ['record' => $this->record]))
                ->visible(fn() => !$this->record->returned_date),

            \Filament\Actions\Action::make('renew')
                ->label('Renew Transaction')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $service = app(\App\Services\TransactionService::class);
                    $result = $service->renewTransaction($this->record);

                    if ($result['success']) {
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Transaction Renewed')
                            ->body($result['message'])
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Cannot Renew')
                            ->body($result['message'])
                            ->send();
                    }
                })
                ->visible(fn() => !$this->record->returned_date),

            \Filament\Actions\DeleteAction::make()
                ->visible(fn() => !$this->record->returned_date),
        ];
    }
}

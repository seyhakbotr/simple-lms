<?php

namespace App\Filament\Staff\Resources\TransactionResource\Pages;

use App\Filament\Staff\Resources\TransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl("view", [
            "record" => $this->record,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn() => $this->record->returned_date !== null)
                ->tooltip(
                    fn() => $this->record->returned_date
                        ? "Cannot delete returned transactions"
                        : null,
                ),
        ];
    }

    protected function beforeSave(): void
    {
        // Prevent editing returned transactions
        if ($this->record->returned_date) {
            Notification::make()
                ->danger()
                ->title("Cannot Edit")
                ->body(
                    "This transaction has been returned and cannot be edited.",
                )
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        Notification::make()->success()->title("Transaction Updated")->send();
    }
}

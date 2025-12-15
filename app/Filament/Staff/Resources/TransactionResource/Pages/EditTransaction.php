<?php

namespace App\Filament\Staff\Resources\TransactionResource\Pages;

use App\Enums\BorrowedStatus;
use App\Filament\Staff\Resources\TransactionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(function () {
                    // Prevent deletion of finalized transactions
                    return in_array($this->record->status, [
                        BorrowedStatus::Returned,
                        BorrowedStatus::Delayed,
                        BorrowedStatus::Lost,
                        BorrowedStatus::Damaged,
                    ]);
                })
                ->tooltip(function () {
                    if (
                        in_array($this->record->status, [
                            BorrowedStatus::Returned,
                            BorrowedStatus::Delayed,
                            BorrowedStatus::Lost,
                            BorrowedStatus::Damaged,
                        ])
                    ) {
                        return "Cannot delete finalized transactions";
                    }
                    return null;
                }),
            Action::make("reset")
                ->outlined()
                ->icon("heroicon-o-arrow-path")
                ->action(fn() => $this->fillForm()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Prevent manipulation of finalized transactions
        if (
            in_array($this->record->status, [
                BorrowedStatus::Returned,
                BorrowedStatus::Delayed,
                BorrowedStatus::Lost,
                BorrowedStatus::Damaged,
            ])
        ) {
            // If transaction is finalized, prevent changing key fields
            $data["status"] = $this->record->status->value;
            $data["returned_date"] = $this->record->returned_date;

            // Only allow updating transaction items' fines/notes
            // Block other changes
            Notification::make()
                ->warning()
                ->title("Transaction is Finalized")
                ->body(
                    "Finalized transactions cannot be modified. Only fine details can be updated.",
                )
                ->send();
        }

        // Auto-determine status based on returned_date ONLY if not manually set to Lost/Damaged
        if (isset($data["returned_date"]) && $data["returned_date"]) {
            $manualStatus = $data["status"] ?? $this->record->status->value;

            // Don't override if staff manually selected Lost or Damaged
            if (
                $manualStatus !== BorrowedStatus::Lost->value &&
                $manualStatus !== BorrowedStatus::Damaged->value
            ) {
                $returnDate = \Illuminate\Support\Carbon::parse(
                    $data["returned_date"],
                );
                $dueDate = $this->record->due_date;

                // Auto-set status based on whether return was on time
                if ($returnDate->lte($dueDate)) {
                    $data["status"] = BorrowedStatus::Returned->value;
                } else {
                    $data["status"] = BorrowedStatus::Delayed->value;
                }
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Update fines when transaction is finalized
        if ($this->record->returned_date) {
            $this->record->updateFines();

            $status = $this->record->status->getLabel();
            $fine = $this->record->total_fine;

            if ($fine > 0) {
                Notification::make()
                    ->success()
                    ->title("Transaction Finalized")
                    ->body(
                        "Status: {$status} | Fine: {$this->record->formatted_total_fine}",
                    )
                    ->send();
            } else {
                Notification::make()
                    ->success()
                    ->title("Transaction Finalized")
                    ->body("Status: {$status} | No fine")
                    ->send();
            }
        }
    }
}

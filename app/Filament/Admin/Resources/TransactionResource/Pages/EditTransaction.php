<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Enums\BorrowedStatus;
use App\Filament\Admin\Resources\TransactionResource;
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
            DeleteAction::make(),
            Action::make("reset")
                ->outlined()
                ->icon("heroicon-o-arrow-path")
                ->action(fn() => $this->fillForm()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-determine status based on returned_date
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
            } else {
                // Keep the manual Lost/Damaged status
                $data["status"] = $manualStatus;
            }
        } elseif (!isset($data["returned_date"]) || !$data["returned_date"]) {
            // If no returned_date, keep as Borrowed unless manually set to Lost/Damaged
            $manualStatus = $data["status"] ?? null;
            if (
                $manualStatus === BorrowedStatus::Lost->value ||
                $manualStatus === BorrowedStatus::Damaged->value
            ) {
                $data["status"] = $manualStatus;
            } else {
                $data["status"] = BorrowedStatus::Borrowed->value;
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
                    ->title("Transaction Updated")
                    ->body(
                        "Status: {$status} | Fine: {$this->record->formatted_total_fine}",
                    )
                    ->send();
            } else {
                Notification::make()
                    ->success()
                    ->title("Transaction Updated")
                    ->body("Status: {$status} | No fine")
                    ->send();
            }
        }
    }
}

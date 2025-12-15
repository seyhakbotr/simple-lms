<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make("reset")
                ->outlined()
                ->icon("heroicon-o-arrow-path")
                ->action(fn() => $this->fillForm()),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Transform the 'transactions' repeater data to 'items' for the service
        if (isset($data["transactions"])) {
            $data["items"] = $data["transactions"];
            unset($data["transactions"]);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $transactionService = app(TransactionService::class);

        try {
            $transaction = $transactionService->createTransaction($data);

            // Success notification
            $user = User::find($transaction->user_id);
            $itemCount = $transaction->items()->count();

            Notification::make()
                ->success()
                ->title("Transaction Created Successfully")
                ->body(
                    "Created transaction for {$user->name}. {$itemCount} book(s) borrowed.",
                )
                ->send();

            return $transaction;
        } catch (ValidationException $e) {
            // Re-throw validation exceptions to show in form
            throw $e;
        } catch (\Exception $e) {
            // Handle any other errors
            Notification::make()
                ->danger()
                ->title("Transaction Creation Failed")
                ->body($e->getMessage())
                ->send();

            $this->halt();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        // Disable default notification since we're handling it custom
        return null;
    }
}

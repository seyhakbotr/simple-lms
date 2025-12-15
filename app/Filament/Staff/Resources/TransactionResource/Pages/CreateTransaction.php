<?php

namespace App\Filament\Staff\Resources\TransactionResource\Pages;

use App\Filament\Staff\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

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

    protected function handleRecordCreation(array $data): Model
    {
        // Create the main transaction
        $transaction = Transaction::create([
            "user_id" => $data["user_id"],
            "borrowed_date" => $data["borrowed_date"],
            "status" => $data["status"],
            "returned_date" => $data["returned_date"] ?? null,
        ]);

        // Create transaction items for each book
        if (isset($data["transactions"]) && is_array($data["transactions"])) {
            foreach ($data["transactions"] as $itemData) {
                TransactionItem::create([
                    "transaction_id" => $transaction->id,
                    "book_id" => $itemData["book_id"],
                    "borrowed_for" => $itemData["borrowed_for"],
                ]);
            }
        }

        return $transaction;
    }
}

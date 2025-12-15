<?php

namespace App\Filament\Admin\Resources\StockTransactionResource\Pages;

use App\Enums\StockAdjustmentType;
use App\Filament\Admin\Resources\StockTransactionResource;
use App\Models\Book;
use App\Models\StockTransaction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateStockTransaction extends CreateRecord
{
    protected static string $resource = StockTransactionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data["user_id"] = Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Validate that we have items
            if (empty($data["items"]) || !is_array($data["items"])) {
                throw new \Exception(
                    "At least one book must be added to the transaction.",
                );
            }

            // Create the stock transaction parent record
            $transaction = StockTransaction::create([
                "user_id" => $data["user_id"],
                "type" => $data["type"],
                "notes" => $data["notes"] ?? null,
                "donator_name" => $data["donator_name"] ?? null,
            ]);

            $type = StockAdjustmentType::from($data["type"]);

            // Process each book item
            foreach ($data["items"] as $item) {
                // Validate item data
                if (empty($item["book_id"]) || empty($item["quantity"])) {
                    continue;
                }

                $book = Book::find($item["book_id"]);
                if (!$book) {
                    continue;
                }

                $oldStock = $book->stock;
                $quantity = (int) $item["quantity"];

                // Calculate new stock based on adjustment type
                if ($type === StockAdjustmentType::Correction) {
                    $newStock = $quantity;
                } elseif (
                    in_array($type, [
                        StockAdjustmentType::Purchase,
                        StockAdjustmentType::Donation,
                    ])
                ) {
                    $newStock = $oldStock + $quantity;
                } else {
                    $newStock = max(0, $oldStock - $quantity);
                }

                // Create the stock transaction item
                $transaction->items()->create([
                    "book_id" => $item["book_id"],
                    "quantity" => $quantity,
                    "old_stock" => $oldStock,
                    "new_stock" => $newStock,
                ]);

                // Update book stock
                $book->update(["stock" => $newStock]);
            }

            return $transaction;
        });
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title("Stock Adjusted")
            ->body(
                "Stock has been successfully adjusted for all selected books.",
            );
    }
}

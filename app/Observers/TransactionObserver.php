<?php

namespace App\Observers;

use App\Enums\BorrowedStatus;
use App\Models\Transaction;
use App\Models\User;
use Filament\Notifications\Notification;

class TransactionObserver
{
    private $admin;

    public function __construct()
    {
        $this->admin = User::with("role")
            ->whereRelation("role", "name", "admin")
            ->first();
    }

    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        $bookCount = $transaction->items()->count();
        $bookText = $bookCount === 1 ? "a book" : $bookCount . " books";

        Notification::make()
            ->title($transaction->user->name . " Borrowed " . $bookText)
            ->icon("heroicon-o-user")
            ->info()
            ->sendToDatabase($this->admin);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        $bookCount = $transaction->items()->count();
        $bookText = $bookCount === 1 ? "a book" : $bookCount . " books";

        if (
            auth()->user()->role->name == "staff" &&
            $transaction->status == BorrowedStatus::Returned
        ) {
            Notification::make()
                ->title("A Borrower Returned " . $bookText)
                ->body(
                    $transaction->user->name .
                        " returned " .
                        $bookText .
                        " on time",
                )
                ->icon("heroicon-o-user")
                ->success()
                ->sendToDatabase($this->admin);
        }

        if (
            auth()->user()->role->name == "staff" &&
            $transaction->status == BorrowedStatus::Delayed
        ) {
            $totalFine = $transaction->total_fine;

            Notification::make()
                ->title("A Borrower Delayed to return " . $bookText)
                ->body(
                    $transaction->user->name .
                        " delayed to return " .
                        $bookText .
                        ', and had to pay a total fine of $' .
                        number_format($totalFine, 2),
                )
                ->icon("heroicon-o-user")
                ->danger()
                ->sendToDatabase($this->admin);
        }
    }
}

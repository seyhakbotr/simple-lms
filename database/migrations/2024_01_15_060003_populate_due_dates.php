<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing transactions with calculated due dates
        $transactions = DB::table('transactions')
            ->whereNull('due_date')
            ->get();

        foreach ($transactions as $transaction) {
            // Get the user's membership type max_borrow_days
            $user = DB::table('users')->find($transaction->user_id);

            $defaultBorrowDays = 14; // Default if no membership

            if ($user && $user->membership_type_id) {
                $membershipType = DB::table('membership_types')
                    ->find($user->membership_type_id);

                if ($membershipType) {
                    $defaultBorrowDays = $membershipType->max_borrow_days;
                }
            }

            // Calculate due date
            $borrowedDate = Carbon::parse($transaction->borrowed_date);
            $dueDate = $borrowedDate->addDays($defaultBorrowDays);

            // Update transaction
            DB::table('transactions')
                ->where('id', $transaction->id)
                ->update(['due_date' => $dueDate]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally clear due dates
        DB::table('transactions')->update(['due_date' => null]);
    }
};

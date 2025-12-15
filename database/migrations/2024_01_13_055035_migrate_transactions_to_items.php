<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, check if the old columns exist (backward compatibility check)
        if (!Schema::hasColumn('transactions', 'book_id')) {
            // New installation - no migration needed
            return;
        }

        // Create a temporary table to store the old transaction data
        DB::statement('CREATE TEMPORARY TABLE temp_old_transactions AS SELECT * FROM transactions');

        // Drop foreign key constraint for book_id
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
        });

        // Remove the old columns from transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['book_id', 'borrowed_for', 'fine']);
        });

        // Migrate data from old structure to new structure
        $oldTransactions = DB::table('temp_old_transactions')->get();

        // Group transactions by user, borrowed_date, and status to consolidate them
        $groupedTransactions = $oldTransactions->groupBy(function ($item) {
            return $item->user_id . '|' . $item->borrowed_date . '|' . $item->status . '|' . ($item->returned_date ?? 'null');
        });

        foreach ($groupedTransactions as $group) {
            // Create one transaction for the group
            $firstItem = $group->first();

            $transactionId = DB::table('transactions')->insertGetId([
                'user_id' => $firstItem->user_id,
                'borrowed_date' => $firstItem->borrowed_date,
                'returned_date' => $firstItem->returned_date,
                'status' => $firstItem->status,
                'created_at' => $firstItem->created_at,
                'updated_at' => $firstItem->updated_at,
            ]);

            // Create transaction items for each book in the group
            foreach ($group as $oldTransaction) {
                DB::table('transaction_items')->insert([
                    'transaction_id' => $transactionId,
                    'book_id' => $oldTransaction->book_id,
                    'borrowed_for' => $oldTransaction->borrowed_for,
                    'fine' => $oldTransaction->fine,
                    'created_at' => $oldTransaction->created_at,
                    'updated_at' => $oldTransaction->updated_at,
                ]);
            }

            // Delete the old transaction records
            DB::table('transactions')
                ->whereIn('id', $group->pluck('id')->toArray())
                ->where('id', '!=', $transactionId)
                ->delete();
        }

        // Drop the temporary table
        DB::statement('DROP TEMPORARY TABLE IF EXISTS temp_old_transactions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the old columns
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('book_id')->nullable()->constrained('books');
            $table->integer('borrowed_for')->nullable();
            $table->integer('fine')->nullable();
        });

        // Migrate data back from new structure to old structure
        $transactions = DB::table('transactions')->get();

        foreach ($transactions as $transaction) {
            $items = DB::table('transaction_items')
                ->where('transaction_id', $transaction->id)
                ->get();

            if ($items->count() > 0) {
                // Update the first item data into the transaction record
                $firstItem = $items->first();
                DB::table('transactions')
                    ->where('id', $transaction->id)
                    ->update([
                        'book_id' => $firstItem->book_id,
                        'borrowed_for' => $firstItem->borrowed_for,
                        'fine' => $firstItem->fine,
                    ]);

                // Create separate transaction records for remaining items
                foreach ($items->skip(1) as $item) {
                    DB::table('transactions')->insert([
                        'user_id' => $transaction->user_id,
                        'borrowed_date' => $transaction->borrowed_date,
                        'returned_date' => $transaction->returned_date,
                        'status' => $transaction->status,
                        'book_id' => $item->book_id,
                        'borrowed_for' => $item->borrowed_for,
                        'fine' => $item->fine,
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at,
                    ]);
                }
            }
        }

        // Delete all transaction items
        DB::table('transaction_items')->truncate();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("transaction_items", function (Blueprint $table) {
            // Add lifecycle_status field for individual items
            $table
                ->string("lifecycle_status", 20)
                ->default("active")
                ->after("item_status")
                ->comment("Lifecycle state: active, returned, lost, archived");

            // Add returned_date for individual items
            $table
                ->date("returned_date")
                ->nullable()
                ->after("lifecycle_status")
                ->comment("Date when this specific item was returned");

            // Add index for better query performance
            $table->index("lifecycle_status");
        });

        // Populate lifecycle_status based on existing item_status field
        DB::statement("
            UPDATE transaction_items
            SET lifecycle_status = CASE
                WHEN item_status = 'returned' THEN 'returned'
                WHEN item_status = 'lost' THEN 'lost'
                WHEN item_status = 'damaged' THEN 'returned'
                ELSE 'active'
            END
        ");

        // Set returned_date for items based on parent transaction
        DB::statement("
            UPDATE transaction_items
            SET returned_date = (
                SELECT returned_date
                FROM transactions
                WHERE transactions.id = transaction_items.transaction_id
            )
            WHERE
                EXISTS (
                    SELECT 1
                    FROM transactions
                    WHERE
                        transactions.id = transaction_items.transaction_id
                        AND transactions.returned_date IS NOT NULL
                )
                AND item_status IN ('returned', 'damaged')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("transaction_items", function (Blueprint $table) {
            $table->dropIndex(["lifecycle_status"]);
            $table->dropColumn(["lifecycle_status", "returned_date"]);
        });
    }
};

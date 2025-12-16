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
        Schema::table("transactions", function (Blueprint $table) {
            // Add lifecycle_status field
            $table
                ->string("lifecycle_status", 20)
                ->default("active")
                ->after("status")
                ->comment(
                    "Lifecycle state: active, completed, cancelled, archived",
                );

            // Add index for better query performance
            $table->index("lifecycle_status");
        });

        // Populate lifecycle_status based on existing status field
        // If transaction has returned_date, it's completed, otherwise active
        DB::statement("
            UPDATE transactions
            SET lifecycle_status = CASE
                WHEN returned_date IS NOT NULL THEN 'completed'
                ELSE 'active'
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("transactions", function (Blueprint $table) {
            $table->dropIndex(["lifecycle_status"]);
            $table->dropColumn("lifecycle_status");
        });
    }
};

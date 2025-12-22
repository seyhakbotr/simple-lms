<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['transaction_id']);

            // Modify the column to be nullable
            $table->foreignId('transaction_id')->nullable()->change();

            // Add foreign key constraint back
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['transaction_id']);

            // Change the column back to not nullable
            $table->foreignId('transaction_id')->change();

            // Add foreign key constraint back (not nullable)
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
        });
    }
};

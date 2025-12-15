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
        Schema::table('transaction_items', function (Blueprint $table) {
            // Fee type and additional fees
            $table->string('item_status')->default('borrowed')->after('borrowed_for'); // borrowed, returned, lost, damaged
            $table->integer('overdue_fine')->default(0)->after('fine'); // Overdue fine in cents
            $table->integer('lost_fine')->default(0)->after('overdue_fine'); // Lost book fine in cents
            $table->integer('damage_fine')->default(0)->after('lost_fine'); // Damage fine in cents
            $table->text('damage_notes')->nullable()->after('damage_fine'); // Notes about damage

            // Rename 'fine' to 'total_fine' for clarity, but keep old column for now
            $table->integer('total_fine')->default(0)->after('damage_notes'); // Sum of all fines
        });

        // Copy existing fine values to total_fine and overdue_fine
        DB::statement('UPDATE transaction_items SET total_fine = COALESCE(fine, 0), overdue_fine = COALESCE(fine, 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn([
                'item_status',
                'overdue_fine',
                'lost_fine',
                'damage_fine',
                'damage_notes',
                'total_fine',
            ]);
        });
    }
};

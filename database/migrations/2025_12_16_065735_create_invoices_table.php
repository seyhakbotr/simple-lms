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
        Schema::create("invoices", function (Blueprint $table) {
            $table->id();
            $table->string("invoice_number")->unique();
            $table
                ->foreignId("transaction_id")
                ->constrained("transactions")
                ->onDelete("cascade");
            $table
                ->foreignId("user_id")
                ->constrained("users")
                ->onDelete("cascade");

            // Fee breakdown (stored in cents)
            $table->integer("overdue_fee")->default(0);
            $table->integer("lost_fee")->default(0);
            $table->integer("damage_fee")->default(0);
            $table->integer("total_amount")->default(0);

            // Payment tracking (stored in cents)
            $table->integer("amount_paid")->default(0);
            $table->integer("amount_due")->default(0);

            // Status
            $table
                ->enum("status", ["unpaid", "partially_paid", "paid", "waived"])
                ->default("unpaid");

            // Dates
            $table->date("invoice_date");
            $table->date("due_date");
            $table->timestamp("paid_at")->nullable();

            // Additional info
            $table->text("notes")->nullable();

            $table->timestamps();

            // Indexes
            $table->index("invoice_number");
            $table->index("user_id");
            $table->index("transaction_id");
            $table->index("status");
            $table->index("invoice_date");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("invoices");
    }
};

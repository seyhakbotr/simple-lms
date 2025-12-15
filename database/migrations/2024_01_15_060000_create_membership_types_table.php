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
        Schema::create('membership_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Basic, Premium, Student, Faculty
            $table->text('description')->nullable();
            $table->integer('max_books_allowed')->default(3); // Max books at once
            $table->integer('max_borrow_days')->default(14); // Default loan period
            $table->integer('renewal_limit')->default(2); // Max renewals per transaction
            $table->decimal('fine_rate', 8, 2)->default(10.00); // Fine per day
            $table->integer('membership_duration_months')->default(12); // Membership validity
            $table->decimal('membership_fee', 8, 2)->default(0); // Annual/registration fee
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_types');
    }
};

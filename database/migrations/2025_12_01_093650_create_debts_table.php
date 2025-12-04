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
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Debtor information
            $table->string('debtor_name'); // Name of person who owes money
            $table->string('debtor_phone')->nullable();
            $table->string('debtor_email')->nullable();

            // Debt details
            $table->decimal('total_amount', 15, 2); // Total debt amount
            $table->decimal('paid_amount', 15, 2)->default(0); // Amount already paid
            $table->text('description')->nullable(); // What the debt is for

            // Priority (1 = highest, 5 = lowest)
            $table->enum('priority', ['1', '2', '3', '4', '5'])->default('3');

            // Payment schedule
            $table->enum('payment_type', ['one_time', 'monthly', 'yearly', 'custom'])->default('one_time');
            $table->decimal('installment_amount', 15, 2)->nullable(); // For recurring payments
            $table->date('due_date')->nullable(); // Final due date or next payment date
            $table->date('start_date')->nullable(); // When the debt started

            // Status
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue', 'cancelled'])->default('pending');

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'priority']);
            $table->index(['user_id', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};

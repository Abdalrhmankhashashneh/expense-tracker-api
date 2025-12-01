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
        // Main balance table - stores user's current balance
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->timestamps();

            $table->unique('user_id'); // Each user has one balance record
        });

        // Balance transactions table - tracks money added/deducted
        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']); // credit = add money, debit = spend money
            $table->decimal('amount', 15, 2);
            $table->enum('source', ['salary', 'freelance', 'gift', 'investment', 'refund', 'transfer', 'other']);
            $table->string('description')->nullable();
            $table->decimal('balance_after', 15, 2); // Balance after this transaction
            $table->foreignId('expense_id')->nullable()->constrained()->onDelete('set null'); // Link to expense if debit
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_transactions');
        Schema::dropIfExists('balances');
    }
};

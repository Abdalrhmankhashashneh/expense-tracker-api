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
        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->decimal('amount', 15, 2); // Payment amount
            $table->date('payment_date'); // When the payment was made
            $table->string('payment_method')->nullable(); // cash, bank transfer, etc.
            $table->text('notes')->nullable();
            
            // Link to balance transaction if payment affects balance
            $table->foreignId('balance_transaction_id')->nullable()->constrained('balance_transactions')->onDelete('set null');
            
            $table->timestamps();
            
            $table->index(['debt_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
    }
};

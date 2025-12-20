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
        Schema::create('lendings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('borrower_name'); // Name of the person who borrowed money
            $table->string('borrower_phone')->nullable();
            $table->string('borrower_email')->nullable();
            $table->decimal('amount', 12, 2); // Original lending amount
            $table->decimal('remaining_amount', 12, 2); // Amount still owed to user
            $table->string('currency', 3)->default('USD');
            $table->text('description')->nullable();
            $table->date('lending_date'); // When the money was lent
            $table->date('expected_return_date')->nullable(); // When they should pay back
            $table->enum('status', ['pending', 'partial', 'paid', 'forgiven'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'lending_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lendings');
    }
};

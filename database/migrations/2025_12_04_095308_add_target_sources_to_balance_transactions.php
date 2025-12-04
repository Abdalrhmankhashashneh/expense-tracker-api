<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add target_id column
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->foreignId('target_id')->nullable()->after('expense_id')->constrained('targets')->nullOnDelete();
        });

        // Update enum to include target sources
        DB::statement("ALTER TABLE balance_transactions MODIFY COLUMN source ENUM('salary', 'freelance', 'gift', 'investment', 'refund', 'transfer', 'expense', 'debt_payment', 'lending', 'lending_return', 'target', 'other')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->dropForeign(['target_id']);
            $table->dropColumn('target_id');
        });

        DB::statement("ALTER TABLE balance_transactions MODIFY COLUMN source ENUM('salary', 'freelance', 'gift', 'investment', 'refund', 'transfer', 'expense', 'debt_payment', 'lending', 'lending_return', 'other')");
    }
};

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
        // Add lending_id column to balance_transactions
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->foreignId('lending_id')->nullable()->after('expense_id')->constrained()->onDelete('set null');
        });

        // Modify the enum to add 'lending' and 'lending_return' sources
        DB::statement("ALTER TABLE balance_transactions MODIFY COLUMN source ENUM('salary', 'freelance', 'gift', 'investment', 'refund', 'transfer', 'expense', 'debt_payment', 'lending', 'lending_return', 'other')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->dropForeign(['lending_id']);
            $table->dropColumn('lending_id');
        });

        DB::statement("ALTER TABLE balance_transactions MODIFY COLUMN source ENUM('salary', 'freelance', 'gift', 'investment', 'refund', 'transfer', 'expense', 'debt_payment', 'other')");
    }
};

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
        DB::statement("ALTER TABLE balance_transactions MODIFY COLUMN source ENUM('salary', 'freelance', 'gift', 'investment', 'refund', 'transfer', 'expense', 'debt_payment', 'other')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE balance_transactions MODIFY COLUMN source ENUM('salary', 'freelance', 'gift', 'investment', 'refund', 'transfer', 'expense', 'other')");
    }
};

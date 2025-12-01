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
        // Modify the enum to add 'expense' source
        DB::statement("ALTER TABLE balance_transactions MODIFY COLUMN source ENUM('salary', 'freelance', 'gift', 'investment', 'refund', 'transfer', 'expense', 'other')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'expense' from enum
        DB::statement("ALTER TABLE balance_transactions MODIFY COLUMN source ENUM('salary', 'freelance', 'gift', 'investment', 'refund', 'transfer', 'other')");
    }
};

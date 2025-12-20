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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // ISO 4217 code (USD, JOD, EUR)
            $table->string('name'); // Full name (US Dollar, Jordanian Dinar)
            $table->string('symbol', 10); // Symbol ($, د.ا, €)
            $table->decimal('exchange_rate', 15, 6)->default(1.000000); // Rate relative to base currency
            $table->boolean('is_default')->default(false); // Default currency (JOD)
            $table->boolean('is_active')->default(true); // Whether currency is available for selection
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};

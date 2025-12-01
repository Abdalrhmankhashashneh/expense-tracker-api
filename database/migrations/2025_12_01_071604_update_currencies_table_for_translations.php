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
        // First, convert existing string values to JSON format
        $currencies = DB::table('currencies')->get();

        foreach ($currencies as $currency) {
            $name = $currency->name;
            // If it's already JSON, skip
            if (is_string($name) && !str_starts_with($name, '{')) {
                DB::table('currencies')
                    ->where('id', $currency->id)
                    ->update(['name' => json_encode(['en' => $name, 'ar' => $name])]);
            }
        }

        // Now change the column type to JSON
        Schema::table('currencies', function (Blueprint $table) {
            $table->json('name')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert JSON back to string (use English name)
        $currencies = DB::table('currencies')->get();

        foreach ($currencies as $currency) {
            $name = json_decode($currency->name, true);
            if (is_array($name)) {
                DB::table('currencies')
                    ->where('id', $currency->id)
                    ->update(['name' => $name['en'] ?? array_values($name)[0] ?? '']);
            }
        }

        Schema::table('currencies', function (Blueprint $table) {
            $table->string('name')->change();
        });
    }
};

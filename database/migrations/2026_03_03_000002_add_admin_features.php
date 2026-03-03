<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_banned')->default(false)->after('warn_before_lending_effect');
            $table->timestamp('banned_at')->nullable()->after('is_banned');
            $table->string('ban_reason')->nullable()->after('banned_at');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action');       // e.g. 'login', 'register', 'export', 'password_change'
            $table->string('module')->nullable(); // e.g. 'auth', 'expenses', 'debts', 'settings'
            $table->string('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('module');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_banned', 'banned_at', 'ban_reason']);
        });

        Schema::dropIfExists('activity_logs');
    }
};

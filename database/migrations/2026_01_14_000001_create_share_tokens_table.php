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
        Schema::create('share_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Polymorphic relationship to shareable models (debts, lendings)
            $table->morphs('shareable');

            // Unique token for URL sharing
            $table->string('token', 64)->unique();

            // Optional expiration
            $table->timestamp('expires_at')->nullable();

            // Access tracking
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();

            // Share type (url, email)
            $table->enum('share_type', ['url', 'email'])->default('url');

            // Email recipient (if shared via email)
            $table->string('recipient_email')->nullable();
            $table->timestamp('email_sent_at')->nullable();

            // Whether the share is active
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'shareable_type', 'shareable_id']);
            $table->index('token');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_tokens');
    }
};

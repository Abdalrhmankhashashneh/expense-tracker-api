<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class ShareToken extends Model
{
    public const TYPE_URL = 'url';
    public const TYPE_EMAIL = 'email';

    public const SHARE_TYPES = [
        self::TYPE_URL,
        self::TYPE_EMAIL,
    ];

    protected $fillable = [
        'user_id',
        'shareable_type',
        'shareable_id',
        'token',
        'expires_at',
        'view_count',
        'last_viewed_at',
        'share_type',
        'recipient_email',
        'email_sent_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'is_active' => 'boolean',
        'view_count' => 'integer',
    ];

    /**
     * Boot method to generate token on creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shareToken) {
            if (empty($shareToken->token)) {
                $shareToken->token = static::generateUniqueToken();
            }
        });
    }

    /**
     * Generate a unique token.
     */
    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /**
     * Get the user that owns this share token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shareable model (debt or lending).
     */
    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if the token has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the token is valid (active and not expired).
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Record a view of this shared resource.
     */
    public function recordView(): void
    {
        $this->increment('view_count');
        $this->update(['last_viewed_at' => now()]);
    }

    /**
     * Deactivate the share token.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get the shareable type in a human-readable format.
     */
    public function getShareableTypeNameAttribute(): string
    {
        return match ($this->shareable_type) {
            Debt::class, 'App\Models\Debt' => 'debt',
            Lending::class, 'App\Models\Lending' => 'lending',
            default => 'unknown',
        };
    }

    /**
     * Scope for active tokens.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for non-expired tokens.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for valid tokens (active and not expired).
     */
    public function scopeValid($query)
    {
        return $query->active()->notExpired();
    }
}

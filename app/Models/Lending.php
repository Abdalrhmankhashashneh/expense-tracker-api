<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lending extends Model
{
    protected $fillable = [
        'user_id',
        'borrower_name',
        'borrower_phone',
        'borrower_email',
        'amount',
        'remaining_amount',
        'currency',
        'description',
        'lending_date',
        'expected_return_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'lending_date' => 'date',
        'expected_return_date' => 'date',
    ];

    /**
     * Get the user that owns the lending
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all payments received for this lending
     */
    public function payments(): HasMany
    {
        return $this->hasMany(LendingPayment::class);
    }

    /**
     * Get the total amount received
     */
    public function getTotalReceivedAttribute(): float
    {
        return (float) $this->amount - (float) $this->remaining_amount;
    }

    /**
     * Get the payment progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if ((float) $this->amount === 0.0) {
            return 0;
        }
        return round(($this->total_received / (float) $this->amount) * 100, 2);
    }

    /**
     * Check if lending is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->expected_return_date || $this->status === 'paid' || $this->status === 'forgiven') {
            return false;
        }
        return $this->expected_return_date->isPast();
    }

    /**
     * Get days until expected return (negative if overdue)
     */
    public function getDaysUntilReturnAttribute(): ?int
    {
        if (!$this->expected_return_date) {
            return null;
        }
        return now()->startOfDay()->diffInDays($this->expected_return_date, false);
    }

    /**
     * Scope for pending lendings
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for partial lendings
     */
    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    /**
     * Scope for overdue lendings
     */
    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['pending', 'partial'])
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', now());
    }

    /**
     * Update status based on remaining amount
     */
    public function updateStatus(): void
    {
        if ((float) $this->remaining_amount <= 0) {
            $this->status = 'paid';
        } elseif ((float) $this->remaining_amount < (float) $this->amount) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }
        $this->save();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LendingPayment extends Model
{
    protected $fillable = [
        'lending_id',
        'amount',
        'payment_date',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Get the lending this payment belongs to
     */
    public function lending(): BelongsTo
    {
        return $this->belongsTo(Lending::class);
    }
}

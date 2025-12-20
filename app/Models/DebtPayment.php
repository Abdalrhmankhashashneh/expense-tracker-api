<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPayment extends Model
{
    /**
     * Payment method constants
     */
    const METHOD_CASH = 'cash';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CARD = 'card';
    const METHOD_MOBILE_PAYMENT = 'mobile_payment';
    const METHOD_OTHER = 'other';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'debt_id',
        'user_id',
        'amount',
        'payment_date',
        'payment_method',
        'notes',
        'balance_transaction_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Get all available payment methods.
     *
     * @return array
     */
    public static function getPaymentMethods(): array
    {
        return [
            self::METHOD_CASH,
            self::METHOD_BANK_TRANSFER,
            self::METHOD_CARD,
            self::METHOD_MOBILE_PAYMENT,
            self::METHOD_OTHER,
        ];
    }

    /**
     * Get the debt that this payment belongs to.
     */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    /**
     * Get the user who recorded this payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the associated balance transaction if any.
     */
    public function balanceTransaction(): BelongsTo
    {
        return $this->belongsTo(BalanceTransaction::class, 'balance_transaction_id');
    }

    /**
     * Scope to filter payments by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter payments by method.
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }
}

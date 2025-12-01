<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="BalanceTransaction",
 *     type="object",
 *     title="Balance Transaction",
 *     required={"id", "user_id", "type", "amount", "source", "balance_after"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", enum={"credit", "debit"}, example="credit"),
 *     @OA\Property(property="amount", type="number", format="float", example=500.00),
 *     @OA\Property(property="source", type="string", enum={"salary", "freelance", "gift", "investment", "refund", "transfer", "other"}, example="salary"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Monthly salary"),
 *     @OA\Property(property="balance_after", type="number", format="float", example=1500.00),
 *     @OA\Property(property="expense_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class BalanceTransaction extends Model
{
    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';

    public const SOURCE_SALARY = 'salary';
    public const SOURCE_FREELANCE = 'freelance';
    public const SOURCE_GIFT = 'gift';
    public const SOURCE_INVESTMENT = 'investment';
    public const SOURCE_REFUND = 'refund';
    public const SOURCE_TRANSFER = 'transfer';
    public const SOURCE_OTHER = 'other';

    public const SOURCES = [
        self::SOURCE_SALARY,
        self::SOURCE_FREELANCE,
        self::SOURCE_GIFT,
        self::SOURCE_INVESTMENT,
        self::SOURCE_REFUND,
        self::SOURCE_TRANSFER,
        self::SOURCE_OTHER,
    ];

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'source',
        'description',
        'balance_after',
        'expense_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * Get the user that owns this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the expense associated with this transaction (if debit).
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Check if this is a credit transaction.
     */
    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    /**
     * Check if this is a debit transaction.
     */
    public function isDebit(): bool
    {
        return $this->type === self::TYPE_DEBIT;
    }

    /**
     * Get source label for display.
     */
    public function getSourceLabelAttribute(): string
    {
        return match($this->source) {
            self::SOURCE_SALARY => 'Salary',
            self::SOURCE_FREELANCE => 'Freelance',
            self::SOURCE_GIFT => 'Gift',
            self::SOURCE_INVESTMENT => 'Investment',
            self::SOURCE_REFUND => 'Refund',
            self::SOURCE_TRANSFER => 'Transfer',
            self::SOURCE_OTHER => 'Other',
            default => 'Unknown',
        };
    }
}

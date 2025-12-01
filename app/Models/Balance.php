<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Balance",
 *     type="object",
 *     title="Balance",
 *     required={"id", "user_id", "current_balance"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="current_balance", type="number", format="float", example=1500.00),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Balance extends Model
{
    protected $fillable = [
        'user_id',
        'current_balance',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
    ];

    /**
     * Get the user that owns this balance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this balance.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Add money to balance.
     */
    public function addMoney(float $amount, string $source, ?string $description = null): BalanceTransaction
    {
        $this->current_balance += $amount;
        $this->save();

        return BalanceTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'credit',
            'amount' => $amount,
            'source' => $source,
            'description' => $description,
            'balance_after' => $this->current_balance,
        ]);
    }

    /**
     * Deduct money from balance.
     */
    public function deductMoney(float $amount, ?int $expenseId = null, ?string $description = null): BalanceTransaction
    {
        $this->current_balance -= $amount;
        $this->save();

        return BalanceTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'debit',
            'amount' => $amount,
            'source' => 'other',
            'description' => $description,
            'balance_after' => $this->current_balance,
            'expense_id' => $expenseId,
        ]);
    }
}

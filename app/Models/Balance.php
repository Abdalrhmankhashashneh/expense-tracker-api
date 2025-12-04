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
            'source' => $expenseId ? BalanceTransaction::SOURCE_EXPENSE : BalanceTransaction::SOURCE_OTHER,
            'description' => $description,
            'balance_after' => $this->current_balance,
            'expense_id' => $expenseId,
        ]);
    }

    /**
     * Refund money to balance (when expense is deleted or updated).
     */
    public function refundMoney(float $amount, ?int $expenseId = null, ?string $description = null): BalanceTransaction
    {
        $this->current_balance += $amount;
        $this->save();

        return BalanceTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'credit',
            'amount' => $amount,
            'source' => BalanceTransaction::SOURCE_REFUND,
            'description' => $description ?? 'Expense refund',
            'balance_after' => $this->current_balance,
            'expense_id' => $expenseId,
        ]);
    }

    /**
     * Deduct money for lending (when user lends money to someone).
     */
    public function deductForLending(float $amount, int $lendingId, string $borrowerName): BalanceTransaction
    {
        $this->current_balance -= $amount;
        $this->save();

        return BalanceTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'debit',
            'amount' => $amount,
            'source' => BalanceTransaction::SOURCE_LENDING,
            'description' => "Lent to {$borrowerName}",
            'balance_after' => $this->current_balance,
            'lending_id' => $lendingId,
        ]);
    }

    /**
     * Add money when lending is returned (payment received).
     */
    public function addLendingReturn(float $amount, int $lendingId, string $borrowerName): BalanceTransaction
    {
        $this->current_balance += $amount;
        $this->save();

        return BalanceTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'credit',
            'amount' => $amount,
            'source' => BalanceTransaction::SOURCE_LENDING_RETURN,
            'description' => "Payment from {$borrowerName}",
            'balance_after' => $this->current_balance,
            'lending_id' => $lendingId,
        ]);
    }

    /**
     * Refund lending (when lending is deleted).
     */
    public function refundLending(float $amount, int $lendingId, string $borrowerName): BalanceTransaction
    {
        $this->current_balance += $amount;
        $this->save();

        return BalanceTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'credit',
            'amount' => $amount,
            'source' => BalanceTransaction::SOURCE_REFUND,
            'description' => "Lending to {$borrowerName} cancelled",
            'balance_after' => $this->current_balance,
            'lending_id' => $lendingId,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Debt",
 *     type="object",
 *     title="Debt",
 *     required={"id", "user_id", "debtor_name", "total_amount", "status"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="debtor_name", type="string", example="John Doe"),
 *     @OA\Property(property="debtor_phone", type="string", nullable=true),
 *     @OA\Property(property="debtor_email", type="string", nullable=true),
 *     @OA\Property(property="total_amount", type="number", format="float", example=1000.00),
 *     @OA\Property(property="paid_amount", type="number", format="float", example=250.00),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="priority", type="string", enum={"1", "2", "3", "4", "5"}),
 *     @OA\Property(property="payment_type", type="string", enum={"one_time", "monthly", "yearly", "custom"}),
 *     @OA\Property(property="installment_amount", type="number", format="float", nullable=true),
 *     @OA\Property(property="due_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="start_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="status", type="string", enum={"pending", "in_progress", "completed", "overdue", "cancelled"}),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Debt extends Model
{
    // Priority levels
    public const PRIORITY_HIGHEST = '1';
    public const PRIORITY_HIGH = '2';
    public const PRIORITY_MEDIUM = '3';
    public const PRIORITY_LOW = '4';
    public const PRIORITY_LOWEST = '5';

    public const PRIORITIES = [
        self::PRIORITY_HIGHEST,
        self::PRIORITY_HIGH,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_LOW,
        self::PRIORITY_LOWEST,
    ];

    // Payment types
    public const PAYMENT_ONE_TIME = 'one_time';
    public const PAYMENT_MONTHLY = 'monthly';
    public const PAYMENT_YEARLY = 'yearly';
    public const PAYMENT_CUSTOM = 'custom';

    public const PAYMENT_TYPES = [
        self::PAYMENT_ONE_TIME,
        self::PAYMENT_MONTHLY,
        self::PAYMENT_YEARLY,
        self::PAYMENT_CUSTOM,
    ];

    // Status
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_OVERDUE,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'user_id',
        'debtor_name',
        'debtor_phone',
        'debtor_email',
        'total_amount',
        'paid_amount',
        'description',
        'priority',
        'payment_type',
        'installment_amount',
        'due_date',
        'start_date',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'due_date' => 'date',
        'start_date' => 'date',
    ];

    /**
     * Get the user that owns this debt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all payments for this debt.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    /**
     * Get the remaining amount to be paid.
     */
    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }
        return round(((float) $this->paid_amount / (float) $this->total_amount) * 100, 2);
    }

    /**
     * Check if the debt is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the debt is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isCompleted();
    }

    /**
     * Record a payment for this debt.
     */
    public function recordPayment(float $amount, ?string $paymentDate = null, ?string $paymentMethod = null, ?string $notes = null): DebtPayment
    {
        $payment = $this->payments()->create([
            'user_id' => $this->user_id,
            'amount' => $amount,
            'payment_date' => $paymentDate ?? now()->toDateString(),
            'payment_method' => $paymentMethod,
            'notes' => $notes,
        ]);

        // Update paid amount
        $this->paid_amount = (float) $this->paid_amount + $amount;

        // Update status based on payment
        if ($this->paid_amount >= $this->total_amount) {
            $this->status = self::STATUS_COMPLETED;
            $this->paid_amount = $this->total_amount; // Cap at total amount
        } elseif ($this->paid_amount > 0) {
            $this->status = self::STATUS_IN_PROGRESS;
        }

        $this->save();

        return $payment;
    }

    /**
     * Update status if overdue.
     */
    public function checkAndUpdateOverdueStatus(): void
    {
        if ($this->isOverdue() && $this->status !== self::STATUS_OVERDUE) {
            $this->status = self::STATUS_OVERDUE;
            $this->save();
        }
    }

    /**
     * Get priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_HIGHEST => 'Highest',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_LOWEST => 'Lowest',
            default => 'Unknown',
        };
    }
}

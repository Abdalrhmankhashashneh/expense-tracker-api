<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Target",
 *     type="object",
 *     title="Target",
 *     required={"id", "user_id", "name", "price"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="New iPhone"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Latest model"),
 *     @OA\Property(property="price", type="number", format="float", example=1200.00),
 *     @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/iphone.jpg"),
 *     @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}, example="high"),
 *     @OA\Property(property="can_afford", type="boolean", example=false),
 *     @OA\Property(property="amount_needed", type="number", format="float", example=700.00),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Target extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'target_amount', // This is the price in the database
        'image_url',
        'priority',
        'status',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
    ];

    protected $appends = [
        'price', // Alias for target_amount
        'can_afford',
        'amount_needed',
    ];

    protected $hidden = [
        'target_amount', // Hide the original column, show 'price' instead
        'saved_amount',
        'icon',
        'color',
        'target_date',
        'completed_at',
    ];

    /**
     * Get the user that owns this target.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias price for target_amount.
     */
    public function getPriceAttribute(): float
    {
        return (float) $this->target_amount;
    }

    /**
     * Allow setting price as target_amount.
     */
    public function setPriceAttribute($value): void
    {
        $this->attributes['target_amount'] = $value;
    }

    /**
     * Check if user can afford this target based on current balance.
     */
    public function getCanAffordAttribute(): bool
    {
        $balance = Balance::where('user_id', $this->user_id)->first();
        if (!$balance) {
            return false;
        }
        return $balance->current_balance >= $this->target_amount;
    }

    /**
     * Get the amount needed to afford this target.
     */
    public function getAmountNeededAttribute(): float
    {
        $balance = Balance::where('user_id', $this->user_id)->first();
        $currentBalance = $balance ? $balance->current_balance : 0;
        return max(0, (float) $this->target_amount - $currentBalance);
    }
}

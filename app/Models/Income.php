<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Income",
 *     type="object",
 *     title="Income",
 *     required={"id", "user_id", "monthly_amount", "effective_from"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="monthly_amount", type="string", example="5000.00"),
 *     @OA\Property(property="effective_from", type="string", format="date", example="2025-12-01"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Income extends Model
{
    use HasFactory;

    protected $table = 'income';

    protected $fillable = [
        'user_id',
        'monthly_amount',
        'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
            'effective_from' => 'date',
        ];
    }

    /**
     * Get the user that owns the income record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get current income (effective date <= today).
     */
    public function scopeCurrent($query)
    {
        return $query->where('effective_from', '<=', now())
                     ->latest('effective_from');
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

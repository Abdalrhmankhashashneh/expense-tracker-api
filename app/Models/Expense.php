<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="Expense",
 *     type="object",
 *     title="Expense",
 *     required={"id", "user_id", "category_id", "amount", "date"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="amount", type="string", example="150.00"),
 *     @OA\Property(property="date", type="string", format="date", example="2025-12-15"),
 *     @OA\Property(property="note", type="string", example="Grocery shopping"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    /**
     * Get the user that owns the expense.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of the expense.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope to filter expenses by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter expenses within a date range.
     */
    public function scopeInDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * Scope to filter expenses by category.
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to filter expenses for a specific month and year.
     */
    public function scopeForMonth($query, $month, $year)
    {
        return $query->whereYear('date', $year)
                     ->whereMonth('date', $month);
    }

    /**
     * Scope to get expenses for the current month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('date', now()->year)
                     ->whereMonth('date', now()->month);
    }

    /**
     * Scope to search expenses by note.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('note', 'like', "%{$term}%");
    }
}

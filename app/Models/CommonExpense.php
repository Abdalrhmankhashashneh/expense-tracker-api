<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="CommonExpense",
 *     type="object",
 *     title="Common Expense",
 *     required={"id", "user_id", "category_id", "name", "amount"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="category_id", type="integer", example=3),
 *     @OA\Property(property="name", type="string", example="Monthly Rent"),
 *     @OA\Property(property="amount", type="string", example="750.00"),
 *     @OA\Property(property="note", type="string", nullable=true, example="Apartment rent"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class CommonExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Get the user that owns this common expense template.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of this common expense template.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope to filter common expenses by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to search by name or note.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('note', 'like', "%{$term}%");
        });
    }
}

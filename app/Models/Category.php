<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

/**
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     title="Category",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="name", type="object", example={"en": "Food", "ar": "طعام"}),
 *     @OA\Property(property="icon", type="string", example="🍔"),
 *     @OA\Property(property="color", type="string", example="#907B60"),
 *     @OA\Property(property="is_default", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Category extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['name'];

    protected $fillable = [
        'user_id',
        'name',
        'icon',
        'color',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the category (null for default categories).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all expenses for this category.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get export history records for this category.
     */
    public function exportHistory()
    {
        return $this->hasMany(ExportHistory::class);
    }

    /**
     * Scope to get only default categories.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get only custom categories.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_default', false);
    }

    /**
     * Scope to get categories accessible by a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_default', true)
              ->orWhere('user_id', $userId);
        });
    }

    /**
     * Get the count of expenses for this category.
     */
    public function getExpensesCountAttribute()
    {
        return $this->expenses()->count();
    }

}

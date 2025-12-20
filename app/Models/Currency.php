<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Currency",
 *     type="object",
 *     title="Currency",
 *     required={"id", "code", "name", "symbol"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", example="JOD"),
 *     @OA\Property(property="name", type="object", example={"en": "Jordanian Dinar", "ar": "دينار أردني"}),
 *     @OA\Property(property="symbol", type="string", example="د.ا"),
 *     @OA\Property(property="exchange_rate", type="string", example="1.000000"),
 *     @OA\Property(property="is_default", type="boolean", example=true),
 *     @OA\Property(property="is_active", type="boolean", example=true)
 * )
 */
class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'exchange_rate' => 'decimal:6',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the translated name based on current locale.
     */
    public function getTranslatedNameAttribute(): string
    {
        $locale = app()->getLocale();
        $name = $this->name;

        if (is_array($name)) {
            return $name[$locale] ?? $name['en'] ?? array_values($name)[0] ?? '';
        }

        return $name ?? '';
    }

    /**
     * Get users using this currency.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope to get only active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get the default currency.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the default currency.
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->first();
    }
}

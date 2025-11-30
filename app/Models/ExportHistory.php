<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExportHistory extends Model
{
    use HasFactory;

    protected $table = 'export_history';

    protected $fillable = [
        'user_id',
        'export_format',
        'date_from',
        'date_to',
        'category_id',
        'record_count',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'record_count' => 'integer',
            'file_size' => 'integer',
        ];
    }

    /**
     * Get the user that owns the export history.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category associated with the export (if filtered).
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope to filter export history by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by export format.
     */
    public function scopeByFormat($query, $format)
    {
        return $query->where('export_format', $format);
    }
}

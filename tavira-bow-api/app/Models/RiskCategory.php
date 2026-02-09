<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskCategory extends Model
{
    protected $fillable = [
        'theme_id',
        'code',
        'name',
        'description',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ========== Relationships ==========

    public function theme(): BelongsTo
    {
        return $this->belongsTo(RiskTheme::class, 'theme_id');
    }

    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class, 'category_id');
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}

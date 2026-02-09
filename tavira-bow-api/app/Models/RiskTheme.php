<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskTheme extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'board_appetite',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'board_appetite' => 'integer',
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ========== Relationships ==========

    public function categories(): HasMany
    {
        return $this->hasMany(RiskCategory::class, 'theme_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(RiskThemePermission::class, 'theme_id');
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

    // ========== Accessors ==========

    public function getRiskCountAttribute(): int
    {
        return $this->categories()->withCount('risks')->get()->sum('risks_count');
    }
}

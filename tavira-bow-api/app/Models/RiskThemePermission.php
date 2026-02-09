<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskThemePermission extends Model
{
    protected $fillable = [
        'theme_id',
        'user_id',
        'can_view',
        'can_edit',
        'can_create',
        'can_delete',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_edit' => 'boolean',
            'can_create' => 'boolean',
            'can_delete' => 'boolean',
        ];
    }

    // ========== Relationships ==========

    public function theme(): BelongsTo
    {
        return $this->belongsTo(RiskTheme::class, 'theme_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== Scopes ==========

    public function scopeWithViewAccess($query)
    {
        return $query->where('can_view', true);
    }

    public function scopeWithEditAccess($query)
    {
        return $query->where('can_edit', true);
    }
}

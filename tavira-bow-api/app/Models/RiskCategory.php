<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $theme_id
 * @property string|null $code
 * @property string|null $name
 * @property string|null $description
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read RiskTheme|null $theme
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Risk> $risks
 */
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

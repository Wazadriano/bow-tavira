<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $code
 * @property string|null $name
 * @property string|null $description
 * @property string|null $control_type
 * @property string|null $frequency
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int $usage_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RiskControl> $riskControls
 */
class ControlLibrary extends Model
{
    protected $table = 'control_library';

    protected $fillable = [
        'code',
        'name',
        'description',
        'control_type',
        'frequency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ========== Relationships ==========

    public function riskControls(): HasMany
    {
        return $this->hasMany(RiskControl::class, 'control_id');
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('control_type', $type);
    }

    // ========== Accessors ==========

    public function getUsageCountAttribute(): int
    {
        return $this->riskControls()->count();
    }
}

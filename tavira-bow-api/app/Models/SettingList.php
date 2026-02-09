<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingList extends Model
{
    protected $fillable = [
        'type',
        'value',
        'label',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('value');
    }

    // ========== Static Helpers ==========

    public static function getByType(string $type): array
    {
        return static::active()
            ->ofType($type)
            ->ordered()
            ->pluck('value')
            ->toArray();
    }

    public static function getDepartments(): array
    {
        return static::getByType('department');
    }

    public static function getActivities(): array
    {
        return static::getByType('activity');
    }

    public static function getEntities(): array
    {
        return static::getByType('entity');
    }
}

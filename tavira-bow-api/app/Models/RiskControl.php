<?php

namespace App\Models;

use App\Enums\ControlImplementationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskControl extends Model
{
    protected $fillable = [
        'risk_id',
        'control_id',
        'implementation_status',
        'effectiveness_score',
        'notes',
        'last_tested_date',
        'next_test_date',
    ];

    protected function casts(): array
    {
        return [
            'implementation_status' => ControlImplementationStatus::class,
            'effectiveness_score' => 'integer',
            'last_tested_date' => 'date',
            'next_test_date' => 'date',
        ];
    }

    // ========== Relationships ==========

    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    public function control(): BelongsTo
    {
        return $this->belongsTo(ControlLibrary::class, 'control_id');
    }

    // ========== Scopes ==========

    public function scopeImplemented($query)
    {
        return $query->where('implementation_status', ControlImplementationStatus::IMPLEMENTED);
    }

    public function scopeNeedsTesting($query)
    {
        return $query->where('next_test_date', '<=', now());
    }

    // ========== Accessors ==========

    public function getIsEffectiveAttribute(): bool
    {
        return $this->effectiveness_score >= 70;
    }

    public function getTestOverdueAttribute(): bool
    {
        return $this->next_test_date && $this->next_test_date->isPast();
    }
}

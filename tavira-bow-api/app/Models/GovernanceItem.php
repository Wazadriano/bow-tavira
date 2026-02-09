<?php

namespace App\Models;

use App\Enums\CurrentStatus;
use App\Enums\GovernanceFrequency;
use App\Enums\GovernanceLocation;
use App\Enums\RAGStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GovernanceItem extends Model
{
    protected $fillable = [
        'ref_no',
        'activity',
        'description',
        'frequency',
        'location',
        'department',
        'responsible_party_id',
        'current_status',
        'rag_status',
        'deadline',
        'completion_date',
        'monthly_update',
        'tags',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => GovernanceFrequency::class,
            'location' => GovernanceLocation::class,
            'current_status' => CurrentStatus::class,
            'rag_status' => RAGStatus::class,
            'deadline' => 'date',
            'completion_date' => 'date',
            'tags' => 'array',
        ];
    }

    // ========== Relationships ==========

    public function responsibleParty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_party_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(GovernanceMilestone::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(GovernanceAttachment::class);
    }

    public function access(): HasMany
    {
        return $this->hasMany(GovernanceItemAccess::class);
    }

    public function risks(): BelongsToMany
    {
        return $this->belongsToMany(Risk::class, 'risk_governance_items');
    }

    // ========== Scopes ==========

    public function scopeOfDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeOfStatus($query, CurrentStatus $status)
    {
        return $query->where('current_status', $status);
    }

    public function scopeOfRag($query, RAGStatus $rag)
    {
        return $query->where('rag_status', $rag);
    }

    // ========== Accessors ==========

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline && $this->deadline->isPast() && !$this->completion_date;
    }
}

<?php

namespace App\Models;

use App\Enums\BAUType;
use App\Enums\CurrentStatus;
use App\Enums\ImpactLevel;
use App\Enums\RAGStatus;
use App\Enums\UpdateFrequency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkItem extends Model
{
    protected $fillable = [
        'ref_no',
        'type',
        'activity',
        'department',
        'description',
        'bau_or_transformative',
        'impact_level',
        'current_status',
        'rag_status',
        'deadline',
        'completion_date',
        'monthly_update',
        'update_frequency',
        'responsible_party_id',
        'tags',
        'priority_item',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'bau_or_transformative' => BAUType::class,
            'impact_level' => ImpactLevel::class,
            'current_status' => CurrentStatus::class,
            'rag_status' => RAGStatus::class,
            'update_frequency' => UpdateFrequency::class,
            'deadline' => 'date',
            'completion_date' => 'date',
            'tags' => 'array',
            'priority_item' => 'boolean',
        ];
    }

    // ========== Relationships ==========

    public function responsibleParty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_party_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'work_item_id');
    }

    public function dependentOn(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'depends_on_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(TaskMilestone::class);
    }

    public function risks(): BelongsToMany
    {
        return $this->belongsToMany(Risk::class, 'risk_work_items');
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

    public function scopePriority($query)
    {
        return $query->where('priority_item', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->whereNull('completion_date');
    }

    // ========== Accessors ==========

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline && $this->deadline->isPast() && ! $this->completion_date;
    }
}

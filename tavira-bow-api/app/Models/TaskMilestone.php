<?php

namespace App\Models;

use App\Enums\RAGStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskMilestone extends Model
{
    protected $fillable = [
        'work_item_id',
        'title',
        'description',
        'due_date',
        'completion_date',
        'rag_status',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completion_date' => 'date',
            'rag_status' => RAGStatus::class,
            'order' => 'integer',
        ];
    }

    // ========== Relationships ==========

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MilestoneAssignment::class, 'milestone_id');
    }

    // ========== Scopes ==========

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeIncomplete($query)
    {
        return $query->whereNull('completion_date');
    }

    // ========== Accessors ==========

    public function getIsCompletedAttribute(): bool
    {
        return $this->completion_date !== null;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && ! $this->completion_date;
    }
}

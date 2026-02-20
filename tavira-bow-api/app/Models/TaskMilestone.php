<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $work_item_id
 * @property string|null $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $target_date
 * @property string|null $status
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $due_date
 * @property-read bool $is_completed
 * @property-read bool $is_overdue
 * @property-read WorkItem|null $workItem
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MilestoneAssignment> $assignments
 */
class TaskMilestone extends Model
{
    protected $fillable = [
        'work_item_id',
        'title',
        'description',
        'target_date',
        'status',
        'order',
    ];

    protected $appends = ['due_date', 'is_completed'];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
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
        return $query->where('status', '!=', 'Completed');
    }

    // ========== Accessors (API compat: frontend expects due_date + is_completed) ==========

    public function getDueDateAttribute(): ?string
    {
        return $this->target_date?->format('Y-m-d');
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'Completed';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->target_date && $this->target_date->isPast() && $this->status !== 'Completed';
    }
}

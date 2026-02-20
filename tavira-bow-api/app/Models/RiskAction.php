<?php

namespace App\Models;

use App\Enums\ActionPriority;
use App\Enums\ActionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $risk_id
 * @property string|null $title
 * @property string|null $description
 * @property int|null $owner_id
 * @property ActionStatus|null $status
 * @property ActionPriority|null $priority
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $is_overdue
 * @property-read Risk|null $risk
 * @property-read User|null $owner
 */
class RiskAction extends Model
{
    protected $fillable = [
        'risk_id',
        'title',
        'description',
        'owner_id',
        'status',
        'priority',
        'due_date',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActionStatus::class,
            'priority' => ActionPriority::class,
            'due_date' => 'date',
            'completed_at' => 'date',
        ];
    }

    // ========== Relationships ==========

    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // ========== Scopes ==========

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [ActionStatus::OPEN, ActionStatus::IN_PROGRESS]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', ActionStatus::COMPLETED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNull('completed_at');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', ActionPriority::HIGH);
    }

    // ========== Accessors ==========

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && ! $this->completed_at;
    }
}

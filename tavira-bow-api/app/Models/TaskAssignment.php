<?php

namespace App\Models;

use App\Enums\AssignmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignment extends Model
{
    protected $fillable = [
        'work_item_id',
        'user_id',
        'assignment_type',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'assignment_type' => AssignmentType::class,
            'acknowledged_at' => 'datetime',
        ];
    }

    // ========== Business Logic ==========

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    // ========== Scopes (acknowledgement) ==========

    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    public function scopeAcknowledged($query)
    {
        return $query->whereNotNull('acknowledged_at');
    }

    // ========== Relationships ==========

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== Scopes ==========

    public function scopeOwners($query)
    {
        return $query->where('assignment_type', AssignmentType::OWNER);
    }

    public function scopeMembers($query)
    {
        return $query->where('assignment_type', AssignmentType::MEMBER);
    }
}

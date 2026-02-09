<?php

namespace App\Models;

use App\Enums\RAGStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class GovernanceMilestone extends Model
{
    protected $fillable = [
        'governance_item_id',
        'title',
        'description',
        'due_date',
        'completion_date',
        'rag_status',
        'order',
        'owner_id',
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

    public function governanceItem(): BelongsTo
    {
        return $this->belongsTo(GovernanceItem::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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
}

<?php

namespace App\Models;

use App\Enums\RAGStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $governance_item_id
 * @property string|null $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $completion_date
 * @property RAGStatus|null $rag_status
 * @property int $order
 * @property int|null $owner_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $is_completed
 * @property-read GovernanceItem|null $governanceItem
 * @property-read User|null $owner
 */
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

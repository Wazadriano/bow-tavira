<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $risk_id
 * @property int|null $work_item_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Risk|null $risk
 * @property-read WorkItem|null $workItem
 */
class RiskWorkItem extends Model
{
    protected $fillable = [
        'risk_id',
        'work_item_id',
    ];

    // ========== Relationships ==========

    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }
}

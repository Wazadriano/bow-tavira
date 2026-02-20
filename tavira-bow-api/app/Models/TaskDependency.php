<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $work_item_id
 * @property int|null $depends_on_id
 * @property string|null $dependency_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read WorkItem|null $workItem
 * @property-read WorkItem|null $dependsOn
 */
class TaskDependency extends Model
{
    protected $fillable = [
        'work_item_id',
        'depends_on_id',
        'dependency_type',
    ];

    // ========== Relationships ==========

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'depends_on_id');
    }
}

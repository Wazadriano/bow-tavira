<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

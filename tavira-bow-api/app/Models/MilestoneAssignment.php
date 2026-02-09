<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilestoneAssignment extends Model
{
    protected $fillable = [
        'milestone_id',
        'user_id',
    ];

    // ========== Relationships ==========

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(TaskMilestone::class, 'milestone_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

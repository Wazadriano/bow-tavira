<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $milestone_id
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read TaskMilestone|null $milestone
 * @property-read User|null $user
 */
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

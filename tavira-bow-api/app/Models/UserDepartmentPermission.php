<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $department
 * @property bool $can_view
 * @property bool $can_edit_status
 * @property bool $can_create_tasks
 * @property bool $can_edit_all
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 */
class UserDepartmentPermission extends Model
{
    protected $fillable = [
        'user_id',
        'department',
        'can_view',
        'can_edit_status',
        'can_create_tasks',
        'can_edit_all',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_edit_status' => 'boolean',
            'can_create_tasks' => 'boolean',
            'can_edit_all' => 'boolean',
        ];
    }

    // ========== Relationships ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

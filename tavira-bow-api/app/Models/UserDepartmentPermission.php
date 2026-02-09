<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

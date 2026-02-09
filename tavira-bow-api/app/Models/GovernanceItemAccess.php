<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernanceItemAccess extends Model
{
    protected $table = 'governance_item_access';

    protected $fillable = [
        'governance_item_id',
        'user_id',
        'can_view',
        'can_edit',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_edit' => 'boolean',
        ];
    }

    // ========== Relationships ==========

    public function governanceItem(): BelongsTo
    {
        return $this->belongsTo(GovernanceItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

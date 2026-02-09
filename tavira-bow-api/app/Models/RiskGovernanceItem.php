<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskGovernanceItem extends Model
{
    protected $fillable = [
        'risk_id',
        'governance_item_id',
    ];

    // ========== Relationships ==========

    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    public function governanceItem(): BelongsTo
    {
        return $this->belongsTo(GovernanceItem::class);
    }
}

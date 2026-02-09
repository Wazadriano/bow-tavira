<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'is_lead',
    ];

    protected function casts(): array
    {
        return [
            'is_lead' => 'boolean',
        ];
    }

    // ========== Relationships ==========

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== Scopes ==========

    public function scopeLeads($query)
    {
        return $query->where('is_lead', true);
    }
}

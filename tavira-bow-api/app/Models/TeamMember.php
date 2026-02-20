<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $team_id
 * @property int|null $user_id
 * @property bool $is_lead
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Team|null $team
 * @property-read User|null $user
 */
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

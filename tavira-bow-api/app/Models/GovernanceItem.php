<?php

namespace App\Models;

use App\Enums\CurrentStatus;
use App\Enums\GovernanceFrequency;
use App\Enums\GovernanceLocation;
use App\Enums\RAGStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string|null $ref_no
 * @property string|null $activity
 * @property string|null $description
 * @property GovernanceFrequency|null $frequency
 * @property GovernanceLocation|null $location
 * @property string|null $department
 * @property int|null $responsible_party_id
 * @property CurrentStatus|null $current_status
 * @property RAGStatus|null $rag_status
 * @property \Illuminate\Support\Carbon|null $deadline
 * @property \Illuminate\Support\Carbon|null $completion_date
 * @property string|null $monthly_update
 * @property array|null $tags
 * @property string|null $file_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $is_overdue
 * @property-read User|null $responsibleParty
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GovernanceMilestone> $milestones
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GovernanceAttachment> $attachments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GovernanceItemAccess> $access
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Risk> $risks
 */
class GovernanceItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'ref_no',
        'activity',
        'description',
        'frequency',
        'location',
        'department',
        'responsible_party_id',
        'current_status',
        'rag_status',
        'deadline',
        'completion_date',
        'monthly_update',
        'tags',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => GovernanceFrequency::class,
            'location' => GovernanceLocation::class,
            'current_status' => CurrentStatus::class,
            'rag_status' => RAGStatus::class,
            'deadline' => 'date',
            'completion_date' => 'date',
            'tags' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['current_status', 'rag_status', 'deadline', 'responsible_party_id'])
            ->logOnlyDirty()
            ->useLogName('governance');
    }

    // ========== Relationships ==========

    public function responsibleParty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_party_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(GovernanceMilestone::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(GovernanceAttachment::class);
    }

    public function access(): HasMany
    {
        return $this->hasMany(GovernanceItemAccess::class);
    }

    public function risks(): BelongsToMany
    {
        return $this->belongsToMany(Risk::class, 'risk_governance_items');
    }

    // ========== Scopes ==========

    public function scopeOfDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeOfStatus($query, CurrentStatus $status)
    {
        return $query->where('current_status', $status);
    }

    public function scopeOfRag($query, RAGStatus $rag)
    {
        return $query->where('rag_status', $rag);
    }

    // ========== Accessors ==========

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline && $this->deadline->isPast() && ! $this->completion_date;
    }
}

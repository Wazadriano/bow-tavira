<?php

namespace App\Models;

use App\Enums\BAUType;
use App\Enums\CurrentStatus;
use App\Enums\ImpactLevel;
use App\Enums\RAGStatus;
use App\Enums\UpdateFrequency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string|null $ref_no
 * @property string|null $type
 * @property string|null $activity
 * @property string|null $department
 * @property string|null $description
 * @property string|null $goal
 * @property BAUType|null $bau_or_transformative
 * @property ImpactLevel|null $impact_level
 * @property CurrentStatus|null $current_status
 * @property RAGStatus|null $rag_status
 * @property \Illuminate\Support\Carbon|null $deadline
 * @property \Illuminate\Support\Carbon|null $completion_date
 * @property string|null $monthly_update
 * @property string|null $comments
 * @property UpdateFrequency|null $update_frequency
 * @property int|null $responsible_party_id
 * @property int|null $department_head_id
 * @property array|null $tags
 * @property bool $priority_item
 * @property string|null $file_path
 * @property float|null $cost_savings
 * @property float|null $cost_efficiency_fte
 * @property float|null $expected_cost
 * @property float|null $revenue_potential
 * @property int|null $back_up_person_id
 * @property string|null $other_item_completion_dependences
 * @property string|null $issues_risks
 * @property string|null $initial_item_provider_editor
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $is_overdue
 * @property-read User|null $responsibleParty
 * @property-read User|null $departmentHead
 * @property-read User|null $backUpPerson
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TaskDependency> $dependencies
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TaskDependency> $dependentOn
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TaskAssignment> $assignments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TaskMilestone> $milestones
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Risk> $risks
 */
class WorkItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'ref_no',
        'type',
        'activity',
        'department',
        'description',
        'goal',
        'bau_or_transformative',
        'impact_level',
        'current_status',
        'rag_status',
        'deadline',
        'completion_date',
        'monthly_update',
        'comments',
        'update_frequency',
        'responsible_party_id',
        'department_head_id',
        'tags',
        'priority_item',
        'file_path',
        'cost_savings',
        'cost_efficiency_fte',
        'expected_cost',
        'revenue_potential',
        'back_up_person_id',
        'other_item_completion_dependences',
        'issues_risks',
        'initial_item_provider_editor',
    ];

    protected function casts(): array
    {
        return [
            'bau_or_transformative' => BAUType::class,
            'impact_level' => ImpactLevel::class,
            'current_status' => CurrentStatus::class,
            'rag_status' => RAGStatus::class,
            'update_frequency' => UpdateFrequency::class,
            'deadline' => 'date',
            'completion_date' => 'date',
            'tags' => 'array',
            'priority_item' => 'boolean',
            'cost_savings' => 'decimal:2',
            'cost_efficiency_fte' => 'decimal:2',
            'expected_cost' => 'decimal:2',
            'revenue_potential' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['current_status', 'rag_status', 'deadline', 'responsible_party_id', 'priority_item'])
            ->logOnlyDirty()
            ->useLogName('work_items');
    }

    // ========== Relationships ==========

    public function responsibleParty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_party_id');
    }

    public function departmentHead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'department_head_id');
    }

    public function backUpPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'back_up_person_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'work_item_id');
    }

    public function dependentOn(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'depends_on_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(TaskMilestone::class);
    }

    public function risks(): BelongsToMany
    {
        return $this->belongsToMany(Risk::class, 'risk_work_items');
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

    public function scopePriority($query)
    {
        return $query->where('priority_item', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->whereNull('completion_date');
    }

    // ========== Accessors ==========

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline && $this->deadline->isPast() && ! $this->completion_date;
    }
}

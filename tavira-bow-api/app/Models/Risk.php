<?php

namespace App\Models;

use App\Enums\AppetiteStatus;
use App\Enums\RAGStatus;
use App\Enums\RiskTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Risk extends Model
{
    use LogsActivity;

    protected $fillable = [
        'category_id',
        'ref_no',
        'name',
        'description',
        'tier',
        'owner_id',
        'responsible_party_id',
        'financial_impact',
        'regulatory_impact',
        'reputational_impact',
        'inherent_probability',
        'inherent_risk_score',
        'inherent_rag',
        'residual_risk_score',
        'residual_rag',
        'appetite_status',
        'monthly_update',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tier' => RiskTier::class,
            'financial_impact' => 'integer',
            'regulatory_impact' => 'integer',
            'reputational_impact' => 'integer',
            'inherent_probability' => 'integer',
            'inherent_risk_score' => 'decimal:2',
            'inherent_rag' => RAGStatus::class,
            'residual_risk_score' => 'decimal:2',
            'residual_rag' => RAGStatus::class,
            'appetite_status' => AppetiteStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['inherent_risk_score', 'residual_risk_score', 'inherent_rag', 'residual_rag', 'appetite_status', 'tier'])
            ->logOnlyDirty()
            ->useLogName('risks');
    }

    // ========== Relationships ==========

    public function category(): BelongsTo
    {
        return $this->belongsTo(RiskCategory::class, 'category_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function responsibleParty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_party_id');
    }

    public function controls(): HasMany
    {
        return $this->hasMany(RiskControl::class);
    }

    public function riskControls(): HasMany
    {
        return $this->hasMany(RiskControl::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(RiskAction::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RiskAttachment::class);
    }

    public function workItems(): BelongsToMany
    {
        return $this->belongsToMany(WorkItem::class, 'risk_work_items');
    }

    public function governanceItems(): BelongsToMany
    {
        return $this->belongsToMany(GovernanceItem::class, 'risk_governance_items');
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeOfTier($query, RiskTier $tier)
    {
        return $query->where('tier', $tier);
    }

    public function scopeHighRisk($query)
    {
        return $query->where('inherent_rag', RAGStatus::RED);
    }

    // ========== Accessors ==========

    public function getInherentImpactAttribute(): float
    {
        return max(
            $this->financial_impact ?? 0,
            $this->regulatory_impact ?? 0,
            $this->reputational_impact ?? 0
        );
    }

    public function getCalculatedInherentScoreAttribute(): float
    {
        return $this->inherent_impact * ($this->inherent_probability ?? 1);
    }

    public function getThemeAttribute(): ?RiskTheme
    {
        return $this->category?->theme;
    }

    // ========== Methods ==========

    public function calculateScores(): void
    {
        $impact = $this->inherent_impact;
        $probability = $this->inherent_probability ?? 1;

        $this->inherent_risk_score = $impact * $probability;
        $this->inherent_rag = $this->calculateRAG($this->inherent_risk_score);

        $controlEffectiveness = 0;
        if (Schema::hasColumn($this->controls()->getRelated()->getTable(), 'effectiveness_score')) {
            $controlEffectiveness = $this->controls()
                ->whereNotNull('effectiveness_score')
                ->avg('effectiveness_score') ?? 0;
        }

        $reduction = $controlEffectiveness / 100;
        $this->residual_risk_score = $this->inherent_risk_score * (1 - $reduction);
        $this->residual_rag = $this->calculateRAG($this->residual_risk_score);

        // Check against appetite
        $boardAppetite = $this->theme?->board_appetite ?? 3;
        $this->appetite_status = $this->residual_risk_score <= $boardAppetite
            ? AppetiteStatus::OK
            : AppetiteStatus::OUTSIDE;
    }

    private function calculateRAG(float $score): RAGStatus
    {
        if ($score <= 4) {
            return RAGStatus::GREEN;
        } elseif ($score <= 9) {
            return RAGStatus::AMBER;
        } else {
            return RAGStatus::RED;
        }
    }
}

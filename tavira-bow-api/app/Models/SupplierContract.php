<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierContract extends Model
{
    protected $fillable = [
        'supplier_id',
        'contract_ref',
        'description',
        'start_date',
        'end_date',
        'amount',
        'currency',
        'auto_renewal',
        'notice_period_days',
        'alert_sent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'amount' => 'decimal:2',
            'auto_renewal' => 'boolean',
            'notice_period_days' => 'integer',
            'alert_sent' => 'boolean',
        ];
    }

    // ========== Relationships ==========

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function entities(): HasMany
    {
        return $this->hasMany(ContractEntity::class, 'contract_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupplierContractAttachment::class, 'contract_id');
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('end_date', '>=', now());
    }

    public function scopeExpiringSoon($query, int $days = 90)
    {
        return $query->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addDays($days));
    }

    // ========== Accessors ==========

    public function getIsActiveAttribute(): bool
    {
        return $this->end_date >= now();
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->end_date) {
            return null;
        }

        return now()->diffInDays($this->end_date, false);
    }

    public function getNeedsAlertAttribute(): bool
    {
        if ($this->alert_sent) {
            return false;
        }

        $daysUntilExpiry = $this->days_until_expiry;
        return $daysUntilExpiry !== null && $daysUntilExpiry <= ($this->notice_period_days ?? 90);
    }
}

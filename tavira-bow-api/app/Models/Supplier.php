<?php

namespace App\Models;

use App\Enums\SupplierLocation;
use App\Enums\SupplierStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Supplier extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
        'category',
        'location',
        'status',
        'is_common_provider',
        'sage_category_id',
        'notes',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'location' => SupplierLocation::class,
            'is_common_provider' => 'boolean',
            'status' => SupplierStatus::class,
            'tags' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'location'])
            ->logOnlyDirty()
            ->useLogName('suppliers');
    }

    // ========== Relationships ==========

    public function sageCategory(): BelongsTo
    {
        return $this->belongsTo(SageCategory::class);
    }

    public function responsibleParty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_party_id');
    }

    public function entities(): HasMany
    {
        return $this->hasMany(SupplierEntity::class);
    }

    public function access(): HasMany
    {
        return $this->hasMany(SupplierAccess::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(SupplierContract::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupplierAttachment::class);
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('status', SupplierStatus::ACTIVE);
    }

    public function scopeCommonProvider($query)
    {
        return $query->where('is_common_provider', true);
    }

    // ========== Accessors ==========

    public function getActiveContractsCountAttribute(): int
    {
        return $this->contracts()
            ->where('end_date', '>=', now())
            ->count();
    }

    public function getTotalInvoicesAmountAttribute(): float
    {
        return $this->invoices()->sum('amount');
    }
}

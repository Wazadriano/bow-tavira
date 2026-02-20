<?php

namespace App\Models;

use App\Enums\SupplierLocation;
use App\Enums\SupplierStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string|null $ref_no
 * @property string|null $name
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $address
 * @property string|null $category
 * @property SupplierLocation|null $location
 * @property SupplierStatus|null $status
 * @property bool $is_common_provider
 * @property int|null $sage_category_id
 * @property int|null $sage_category_2_id
 * @property int|null $responsible_party_id
 * @property string|null $notes
 * @property array|null $tags
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int $active_contracts_count
 * @property-read float $total_invoices_amount
 * @property-read SageCategory|null $sageCategory
 * @property-read SageCategory|null $sageCategory2
 * @property-read User|null $responsibleParty
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SupplierEntity> $entities
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SupplierAccess> $access
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SupplierContract> $contracts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SupplierInvoice> $invoices
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SupplierAttachment> $attachments
 */
class Supplier extends Model
{
    use LogsActivity;

    protected $fillable = [
        'ref_no',
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
        'sage_category_2_id',
        'responsible_party_id',
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

    public function sageCategory2(): BelongsTo
    {
        return $this->belongsTo(SageCategory::class, 'sage_category_2_id');
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

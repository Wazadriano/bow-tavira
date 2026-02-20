<?php

namespace App\Models;

use App\Enums\InvoiceFrequency;
use App\Enums\InvoiceStatus;
use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $supplier_id
 * @property string|null $invoice_ref
 * @property string|null $description
 * @property float|null $amount
 * @property string|null $currency
 * @property \Illuminate\Support\Carbon|null $invoice_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $paid_date
 * @property InvoiceFrequency|null $frequency
 * @property InvoiceStatus|null $status
 * @property string|null $notes
 * @property int|null $sage_category_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float $amount_gbp
 * @property-read bool $is_overdue
 * @property-read Supplier|null $supplier
 * @property-read SageCategory|null $sageCategory
 */
class SupplierInvoice extends Model
{
    protected $fillable = [
        'supplier_id',
        'invoice_ref',
        'description',
        'amount',
        'currency',
        'invoice_date',
        'due_date',
        'paid_date',
        'frequency',
        'status',
        'notes',
        'sage_category_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'invoice_date' => 'date',
            'due_date' => 'date',
            'paid_date' => 'date',
            'frequency' => InvoiceFrequency::class,
            'status' => InvoiceStatus::class,
        ];
    }

    // ========== Relationships ==========

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function sageCategory(): BelongsTo
    {
        return $this->belongsTo(SageCategory::class, 'sage_category_id');
    }

    // ========== Scopes ==========

    public function scopePending($query)
    {
        return $query->where('status', InvoiceStatus::PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatus::PAID);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', InvoiceStatus::PENDING)
            ->where('due_date', '<', now());
    }

    // ========== Accessors ==========

    public function getInvoiceRefAttribute(): ?string
    {
        return $this->attributes['invoice_ref'] ?? $this->attributes['invoice_number'] ?? null;
    }

    public function getAmountGbpAttribute(): float
    {
        if ($this->amount === null) {
            return 0.0;
        }

        $currency = $this->currency ?? 'EUR';

        return app(CurrencyConversionService::class)->toGbp((float) $this->amount, $currency);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === InvoiceStatus::PENDING
            && $this->due_date
            && $this->due_date->isPast();
    }
}

<?php

namespace App\Models;

use App\Enums\InvoiceFrequency;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === InvoiceStatus::PENDING
            && $this->due_date
            && $this->due_date->isPast();
    }
}

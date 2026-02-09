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

    // ========== Scopes ==========

    public function scopePending($query)
    {
        return $query->where('status', InvoiceStatus::Pending);
    }

    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatus::Paid);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', InvoiceStatus::Pending)
            ->where('due_date', '<', now());
    }

    // ========== Accessors ==========

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === InvoiceStatus::Pending
            && $this->due_date
            && $this->due_date->isPast();
    }
}

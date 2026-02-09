<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierEntity extends Model
{
    protected $fillable = [
        'supplier_id',
        'entity',
    ];

    // ========== Relationships ==========

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}

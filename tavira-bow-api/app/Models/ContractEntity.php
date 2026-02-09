<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractEntity extends Model
{
    protected $fillable = [
        'contract_id',
        'entity',
    ];

    // ========== Relationships ==========

    public function contract(): BelongsTo
    {
        return $this->belongsTo(SupplierContract::class, 'contract_id');
    }
}

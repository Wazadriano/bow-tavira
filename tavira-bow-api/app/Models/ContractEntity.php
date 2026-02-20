<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $contract_id
 * @property string|null $entity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read SupplierContract|null $contract
 */
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

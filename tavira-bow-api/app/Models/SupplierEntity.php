<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $supplier_id
 * @property string|null $entity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Supplier|null $supplier
 */
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

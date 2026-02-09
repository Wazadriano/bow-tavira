<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierAccess extends Model
{
    protected $table = 'supplier_access';

    protected $fillable = [
        'supplier_id',
        'user_id',
        'can_view',
        'can_edit',
        'can_create',
        'can_delete',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_edit' => 'boolean',
            'can_create' => 'boolean',
            'can_delete' => 'boolean',
        ];
    }

    // ========== Relationships ==========

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

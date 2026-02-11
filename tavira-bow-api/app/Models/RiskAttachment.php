<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAttachment extends Model
{
    protected $fillable = [
        'risk_id',
        'filename',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'version',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'version' => 'integer',
        ];
    }

    // ========== Relationships ==========

    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ========== Accessors ==========

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}

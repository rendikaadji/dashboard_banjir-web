<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ts',
        'user_id',
        'action',
        'entity',
        'entity_id',
        'before_value',
        'after_value',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'ts' => 'datetime',
            'before_value' => 'array',
            'after_value' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

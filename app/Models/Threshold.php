<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Threshold extends Model
{
    use HasFactory;

    public const LEVEL_WASPADA = 1;

    public const LEVEL_SIAGA = 2;

    public const LEVEL_KRITIS = 3;

    protected $fillable = [
        'scope',
        'scope_ref',
        'level',
        'metric',
        'operator',
        'value_low',
        'value_high',
        'sustain_seconds',
        'hysteresis_cm',
        'cooldown_minutes',
        'is_active',
        'effective_from',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'value_low' => 'float',
            'value_high' => 'float',
            'sustain_seconds' => 'integer',
            'hysteresis_cm' => 'float',
            'cooldown_minutes' => 'integer',
            'is_active' => 'boolean',
            'effective_from' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

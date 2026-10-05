<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'ts',
        'node_id',
        'manual_cm',
        'sensor_cm',
        'error_pct',
        'observer',
        'photo_url',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'ts' => 'datetime',
            'manual_cm' => 'float',
            'sensor_cm' => 'float',
            'error_pct' => 'float',
        ];
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id', 'node_id');
    }
}

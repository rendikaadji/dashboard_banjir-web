<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reading extends Model
{
    use HasFactory;

    protected $fillable = [
        'ts',
        'node_id',
        'seq',
        'distance_cm',
        'water_level_cm',
        'water_level_ewma',
        'rise_rate_cmh',
        'flow_lpm',
        'flow_velocity_ms',
        'rainfall_mm',
        'rain_rate_mmh',
        'wind_speed_ms',
        'battery_v',
        'solar_v',
        'rssi',
        'quality',
        'is_buffered',
    ];

    protected function casts(): array
    {
        return [
            'ts' => 'datetime',
            'seq' => 'integer',
            'distance_cm' => 'float',
            'water_level_cm' => 'float',
            'water_level_ewma' => 'float',
            'rise_rate_cmh' => 'float',
            'flow_lpm' => 'float',
            'flow_velocity_ms' => 'float',
            'rainfall_mm' => 'float',
            'rain_rate_mmh' => 'float',
            'wind_speed_ms' => 'float',
            'battery_v' => 'float',
            'solar_v' => 'float',
            'rssi' => 'integer',
            'is_buffered' => 'boolean',
        ];
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id', 'node_id');
    }
}

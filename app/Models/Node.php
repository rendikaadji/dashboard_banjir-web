<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Node extends Model
{
    use HasFactory;

    public const TYPE_WATER_LEVEL = 'WATER_LEVEL';

    public const TYPE_FLOW = 'FLOW';

    public const TYPE_RAINFALL = 'RAINFALL';

    public const TYPE_GATEWAY = 'GATEWAY';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_STALE = 'STALE';

    public const STATUS_OFFLINE = 'OFFLINE';

    public const STATUS_MAINTENANCE = 'MAINTENANCE';

    public const STATUS_RETIRED = 'RETIRED';

    protected $fillable = [
        'node_id',
        'site_id',
        'zone_id',
        'node_type',
        'label',
        'latitude',
        'longitude',
        'mount_height_cm',
        'ground_elev_cm',
        'calib_offset_cm',
        'min_valid_cm',
        'max_valid_cm',
        'fusion_weight',
        'is_critical',
        'status',
        'firmware_version',
        'installed_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'mount_height_cm' => 'float',
            'ground_elev_cm' => 'float',
            'calib_offset_cm' => 'float',
            'min_valid_cm' => 'float',
            'max_valid_cm' => 'float',
            'fusion_weight' => 'float',
            'is_critical' => 'boolean',
            'installed_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class, 'node_id', 'node_id');
    }

    public function latestReading(): HasOne
    {
        return $this->hasOne(Reading::class, 'node_id', 'node_id')->latestOfMany('ts');
    }

    public function healthLogs(): HasMany
    {
        return $this->hasMany(NodeHealth::class, 'node_id', 'node_id');
    }

    public function manualObservations(): HasMany
    {
        return $this->hasMany(ManualObservation::class, 'node_id', 'node_id');
    }
}

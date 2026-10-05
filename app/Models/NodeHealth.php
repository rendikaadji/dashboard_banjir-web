<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NodeHealth extends Model
{
    use HasFactory;

    protected $table = 'node_health';

    protected $fillable = [
        'ts',
        'node_id',
        'uptime_s',
        'free_heap',
        'reboot_reason',
        'buffer_depth',
        'battery_v',
        'rssi',
    ];

    protected function casts(): array
    {
        return [
            'ts' => 'datetime',
            'uptime_s' => 'integer',
            'free_heap' => 'integer',
            'buffer_depth' => 'integer',
            'battery_v' => 'float',
            'rssi' => 'integer',
        ];
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id', 'node_id');
    }
}

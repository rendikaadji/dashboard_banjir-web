<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{
    use HasFactory;

    public const STATUS_TRIGGERED = 'TRIGGERED';

    public const STATUS_NOTIFYING = 'NOTIFYING';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_ACKNOWLEDGED = 'ACKNOWLEDGED';

    public const STATUS_RESOLVED = 'RESOLVED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'site_id',
        'zone_id',
        'level',
        'status',
        'trigger_type',
        'trigger_node_id',
        'trigger_value',
        'trigger_metric',
        'snapshot',
        'message_text',
        'started_at',
        'grace_period_ends_at',
        'escalated_from',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
        'resolution_note',
        'is_false_alarm',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'trigger_value' => 'float',
            'snapshot' => 'array',
            'started_at' => 'datetime',
            'grace_period_ends_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'is_false_alarm' => 'boolean',
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

    public function triggerNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'trigger_node_id', 'node_id');
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AlertDelivery::class);
    }

    public function getLevelNameAttribute(): string
    {
        return match ($this->level) {
            1 => 'WASPADA',
            2 => 'SIAGA',
            3 => 'KRITIS',
            default => 'AMAN',
        };
    }
}

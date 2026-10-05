<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertDelivery extends Model
{
    use HasFactory;

    public const CHANNEL_WHATSAPP = 'WHATSAPP';

    public const CHANNEL_TELEGRAM = 'TELEGRAM';

    public const CHANNEL_SIREN = 'SIREN';

    public const CHANNEL_LED = 'LED';

    public const CHANNEL_WEB_PUSH = 'WEB_PUSH';

    public const STATUS_QUEUED = 'QUEUED';

    public const STATUS_SENT = 'SENT';

    public const STATUS_DELIVERED = 'DELIVERED';

    public const STATUS_READ = 'READ';

    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'alert_id',
        'channel',
        'target',
        'queued_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'latency_ms',
        'status',
        'error_code',
        'retry_count',
    ];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'latency_ms' => 'integer',
            'retry_count' => 'integer',
        ];
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }
}

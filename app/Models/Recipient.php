<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recipient extends Model
{
    use HasFactory;

    public const ROLE_WARGA = 'WARGA';

    public const ROLE_KETUA_RT = 'KETUA_RT';

    public const ROLE_SATPAM = 'SATPAM';

    public const ROLE_SATGAS = 'SATGAS';

    public const ROLE_PENGURUS_RW = 'PENGURUS_RW';

    protected $fillable = [
        'full_name',
        'phone_e164',
        'zone_id',
        'role_tag',
        'min_level',
        'channel',
        'consent_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_level' => 'integer',
            'consent_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}

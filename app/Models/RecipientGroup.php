<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipientGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'wa_group_id',
        'zone_id',
        'min_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}

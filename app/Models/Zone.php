<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'code',
        'name',
        'household_cnt',
        'flood_prone',
    ];

    protected function casts(): array
    {
        return [
            'flood_prone' => 'boolean',
            'household_cnt' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(Recipient::class);
    }

    public function recipientGroups(): HasMany
    {
        return $this->hasMany(RecipientGroup::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}

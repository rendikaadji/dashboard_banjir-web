<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'SUPER_ADMIN';

    public const ROLE_TECH_ADMIN = 'TECH_ADMIN';

    public const ROLE_RW_BOARD = 'RW_BOARD';

    public const ROLE_OPERATOR = 'OPERATOR';

    public const ROLE_VIEWER = 'VIEWER';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'phone_e164',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isTechAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_TECH_ADMIN], true);
    }

    public function isRwBoard(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_TECH_ADMIN, self::ROLE_RW_BOARD], true);
    }

    public function isOperator(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_TECH_ADMIN, self::ROLE_RW_BOARD, self::ROLE_OPERATOR], true);
    }
}

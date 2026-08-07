<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'login_id',
        'name',
        'password',
        'role',
        'is_active',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'             => 'hashed',
            'role'                 => UserRole::class,
            'is_active'            => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at'        => 'datetime',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'assigned_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isViewer(): bool
    {
        return $this->role === UserRole::Viewer;
    }

    /** 登録・編集系の操作が可能か */
    public function canWrite(): bool
    {
        return ! $this->isViewer();
    }
}

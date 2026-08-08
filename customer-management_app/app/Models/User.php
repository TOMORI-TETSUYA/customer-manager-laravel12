<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Gate;

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

    /**
     * 顧客の担当者として選べるユーザーに絞り込む。
     *
     * 有効なアカウントのうち、運用管理用のアカウント
     * (config/auth.php の non_assignable_login_ids)を除く。
     * 画面の選択肢と入力チェックの両方でこのスコープを使い、
     * 「一覧には出ないが改ざんすれば指定できる」状態を作らない。
     */
    public function scopeAssignable(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereNotIn('login_id', config('auth.non_assignable_login_ids', []));
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }

    public function isMember(): bool
    {
        return $this->role === UserRole::Member;
    }

    /**
     * 顧客・契約の登録編集が可能か (§16)。
     *
     * 現在は3ロールとも可能。対応履歴と請求・入金については
     * 画面単位の Gate (access-dialog / access-payment) で別途制限する。
     */
    public function canWrite(): bool
    {
        return true;
    }

    /**
     * ログイン直後・トップ(/)アクセス時に開く画面 (§16)。
     *
     * メンバーはダッシュボードを開けないため顧客一覧へ送る。
     * 判定は Gate に委ねて、権限定義とずれないようにする。
     */
    public function homeRouteName(): string
    {
        return Gate::forUser($this)->allows('access-dashboard')
            ? 'dashboard'
            : 'customers.index';
    }

    /**
     * ユーザー管理の一覧に表示してよいロール。
     *
     * @return list<string>
     */
    public function visibleUserRoles(): array
    {
        return UserRole::toValues($this->role->visibleRoles());
    }

    /**
     * 新しいユーザーとして作成してよいロール。
     *
     * @return list<UserRole>
     */
    public function creatableUserRoles(): array
    {
        return $this->role->creatableRoles();
    }
}

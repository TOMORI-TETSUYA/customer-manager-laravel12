<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * 初期管理者の作成 (§36-10)
 *
 * 平文パスワードをSQLへ記載しないため、管理者はこのコマンドで作成する。
 * 発行された初期パスワードは初回ログイン時に変更が強制される。
 *
 *   php artisan app:create-admin admin "システム管理者"
 */
class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin
        {login_id : ログインID}
        {name : 表示名}';

    protected $description = '初期管理者ユーザーを作成し、初期パスワードを表示する';

    public function handle(): int
    {
        $loginId = (string) $this->argument('login_id');
        $name    = (string) $this->argument('name');

        if (User::query()->where('login_id', $loginId)->exists()) {
            $this->error("ログインID「{$loginId}」は既に存在します。");

            return self::FAILURE;
        }

        $initialPassword = Str::password(16);

        User::query()->create([
            'login_id'             => $loginId,
            'name'                 => $name,
            'password'             => $initialPassword,
            'role'                 => UserRole::Admin->value,
            'is_active'            => true,
            'must_change_password' => true,
        ]);

        $this->info('管理者を作成しました。');
        $this->line("ログインID    : {$loginId}");
        $this->line("初期パスワード: {$initialPassword}");
        $this->warn('初期パスワードは今だけ表示されます。初回ログイン時に変更が必要です。');

        return self::SUCCESS;
    }
}

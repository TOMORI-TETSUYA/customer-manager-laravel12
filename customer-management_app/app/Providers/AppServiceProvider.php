<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Customer;
use App\Models\User;
use App\Policies\CustomerPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::policy(Customer::class, CustomerPolicy::class);

        // ------------------------------------------------------------------
        // 静的ファイルのキャッシュ対策
        //
        // Nginx は css / js / 画像に7日間のキャッシュを設定している。
        // そのままだと、修正してもブラウザが古いファイルを使い続けるため、
        // ファイルの更新時刻をクエリに付けて、変更したときだけ取り直させる。
        //
        //   使い方(Blade): <link rel="stylesheet" href="{{ $phAsset('/css/app.css') }}">
        //   出力例       : /css/app.css?v=1754624400
        // ------------------------------------------------------------------
        View::share('phAsset', static function (string $path): string {
            // 公開ディレクトリは Laravel 本体の隣にある customer-management
            $file = dirname(base_path()) . '/customer-management' . $path;

            if (! is_file($file)) {
                return $path;
            }

            return $path . '?v=' . filemtime($file);
        });

        // 顧客・契約の登録編集 (§16: 3ロールとも可能)
        Gate::define('write-data', fn (User $user): bool => $user->canWrite());

        // ------------------------------------------------------------------
        // 画面単位のアクセス権 (§16)
        //
        // メンバーがアクセスできるのは顧客管理・契約管理・ユーザー管理のみ。
        // 顧客管理と契約管理は全ロールが対象なので Gate を設けていない。
        // ------------------------------------------------------------------

        // ダッシュボード
        Gate::define('access-dashboard', fn (User $user): bool => ! $user->isMember());

        // 対応履歴 (/dialog と対応履歴の登録)
        Gate::define('access-dialog', fn (User $user): bool => ! $user->isMember());

        // 請求・入金 (/payment と請求・入金の登録)
        Gate::define('access-payment', fn (User $user): bool => ! $user->isMember());

        // 操作履歴
        Gate::define('view-audit-logs', fn (User $user): bool => ! $user->isMember());

        // ユーザー管理の閲覧。全ロールが開けるが、一覧に出る範囲は
        // UserRole::visibleRoles() でロールごとに絞り込む。
        Gate::define('access-users', fn (User $user): bool => true);

        // ユーザーの作成・有効無効の切り替え (管理者と職員のみ)
        Gate::define('manage-users', fn (User $user): bool => ! $user->isMember());
    }
}

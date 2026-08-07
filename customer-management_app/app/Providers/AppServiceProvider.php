<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Customer;
use App\Models\User;
use App\Policies\CustomerPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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

        // 登録系操作(対応履歴・契約・請求・入金)
        Gate::define('write-data', fn (User $user): bool => $user->canWrite());

        // 管理者専用
        Gate::define('manage-users', fn (User $user): bool => $user->isAdmin());
        Gate::define('view-audit-logs', fn (User $user): bool => $user->isAdmin());
    }
}

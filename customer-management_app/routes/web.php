<?php

declare(strict_types=1);

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CustomerContactController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------
// 未認証 (AUTH-01)
// ---------------------------------------------------------------------
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// ---------------------------------------------------------------------
// 認証済み
// ---------------------------------------------------------------------
// user.active : ログイン後に無効化されたユーザーをその場で締め出す。
// login.fresh : 最後のログインから一定日数が過ぎたら強制ログアウトする。
// どちらもパスワード変更画面を含めて塞ぐため password.changed の外側に置く。
Route::middleware(['auth', 'user.active', 'login.fresh'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // 初回パスワード変更 (password.changed ミドルウェアの外)
    Route::get('/password/change', [PasswordChangeController::class, 'edit'])
        ->name('password.change');
    Route::put('/password/change', [PasswordChangeController::class, 'update'])
        ->name('password.update');

    Route::middleware('password.changed')->group(function (): void {
        // ロールごとの入口 (§16)。
        // メンバーはダッシュボードを開けないため顧客一覧へ送る。
        Route::get('/', fn (Request $request) => redirect()
            ->route($request->user()->homeRouteName()));

        // DASH-01
        Route::get('/dashboard', DashboardController::class)
            ->middleware('can:access-dashboard')
            ->name('dashboard');

        // -------------------------------------------------------------
        // CUS-01 〜 CUS-04 顧客管理 (全ロール)
        // -------------------------------------------------------------
        Route::get('/customers', [CustomerController::class, 'index'])
            ->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])
            ->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])
            ->name('customers.store');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])
            ->whereNumber('customer')
            ->name('customers.show');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])
            ->whereNumber('customer')
            ->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])
            ->whereNumber('customer')
            ->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
            ->whereNumber('customer')
            ->name('customers.destroy');
        Route::post('/customers/{id}/restore', [CustomerController::class, 'restore'])
            ->whereNumber('id')
            ->name('customers.restore');

        // -------------------------------------------------------------
        // CTR-01 契約管理 (全ロール)
        //   /contract は旧 /customers?contract_state=active
        // -------------------------------------------------------------
        Route::get('/contract', [CustomerController::class, 'contract'])
            ->name('contract.index');
        Route::get('/customers/{customer}/contracts/create', [ContractController::class, 'create'])
            ->whereNumber('customer')
            ->name('contracts.create');
        Route::post('/customers/{customer}/contracts', [ContractController::class, 'store'])
            ->whereNumber('customer')
            ->name('contracts.store');

        // -------------------------------------------------------------
        // CON-01 対応履歴 (管理者・職員のみ)
        //   /dialog は旧 /customers?sort=contacted_desc
        // -------------------------------------------------------------
        Route::middleware('can:access-dialog')->group(function (): void {
            Route::get('/dialog', [CustomerController::class, 'dialog'])
                ->name('dialog.index');
            Route::get('/customers/{customer}/contacts/create', [CustomerContactController::class, 'create'])
                ->whereNumber('customer')
                ->name('contacts.create');
            Route::post('/customers/{customer}/contacts', [CustomerContactController::class, 'store'])
                ->whereNumber('customer')
                ->name('contacts.store');
        });

        // -------------------------------------------------------------
        // INV-01 / PAY-01 請求・入金 (管理者・職員のみ)
        //   /payment は旧 /customers?payment_state=unpaid
        // -------------------------------------------------------------
        Route::middleware('can:access-payment')->group(function (): void {
            Route::get('/payment', [CustomerController::class, 'payment'])
                ->name('payment.index');
            Route::get('/customers/{customer}/invoices/create', [InvoiceController::class, 'create'])
                ->whereNumber('customer')
                ->name('invoices.create');
            Route::post('/customers/{customer}/invoices', [InvoiceController::class, 'store'])
                ->whereNumber('customer')
                ->name('invoices.store');
            Route::get('/invoices/{invoice}/payments/create', [PaymentController::class, 'create'])
                ->whereNumber('invoice')
                ->name('payments.create');
            Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])
                ->whereNumber('invoice')
                ->name('payments.store');
        });

        // -------------------------------------------------------------
        // USR-01 / LOG-01 管理
        // -------------------------------------------------------------
        Route::prefix('admin')->name('admin.')->group(function (): void {
            // 一覧は全ロールが開ける。表示される範囲はロールごとに絞られる。
            Route::get('/users', [UserController::class, 'index'])
                ->middleware('can:access-users')
                ->name('users.index');

            // 作成と有効無効の切り替えは管理者・職員のみ
            Route::middleware('can:manage-users')->group(function (): void {
                Route::get('/users/create', [UserController::class, 'create'])
                    ->name('users.create');
                Route::post('/users', [UserController::class, 'store'])
                    ->name('users.store');
                Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])
                    ->whereNumber('user')
                    ->name('users.toggle');

                // パスワードの再発行(自動生成)
                Route::post('/users/{user}/regenerate-password', [UserController::class, 'regeneratePassword'])
                    ->whereNumber('user')
                    ->name('users.regenerate-password');

                // 削除(論理削除)
                Route::delete('/users/{user}', [UserController::class, 'destroy'])
                    ->whereNumber('user')
                    ->name('users.destroy');
            });

            Route::get('/audit-logs', [AuditLogController::class, 'index'])
                ->middleware('can:view-audit-logs')
                ->name('audit-logs.index');
        });
    });
});

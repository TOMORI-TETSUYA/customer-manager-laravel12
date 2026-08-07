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
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // 初回パスワード変更 (password.changed ミドルウェアの外)
    Route::get('/password/change', [PasswordChangeController::class, 'edit'])
        ->name('password.change');
    Route::put('/password/change', [PasswordChangeController::class, 'update'])
        ->name('password.update');

    Route::middleware('password.changed')->group(function (): void {
        Route::get('/', fn () => redirect()->route('dashboard'));

        // DASH-01
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        // CUS-01 〜 CUS-04
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

        // CON-01
        Route::get('/customers/{customer}/contacts/create', [CustomerContactController::class, 'create'])
            ->whereNumber('customer')
            ->name('contacts.create');
        Route::post('/customers/{customer}/contacts', [CustomerContactController::class, 'store'])
            ->whereNumber('customer')
            ->name('contacts.store');

        // CTR-01
        Route::get('/customers/{customer}/contracts/create', [ContractController::class, 'create'])
            ->whereNumber('customer')
            ->name('contracts.create');
        Route::post('/customers/{customer}/contracts', [ContractController::class, 'store'])
            ->whereNumber('customer')
            ->name('contracts.store');

        // INV-01
        Route::get('/customers/{customer}/invoices/create', [InvoiceController::class, 'create'])
            ->whereNumber('customer')
            ->name('invoices.create');
        Route::post('/customers/{customer}/invoices', [InvoiceController::class, 'store'])
            ->whereNumber('customer')
            ->name('invoices.store');

        // PAY-01
        Route::get('/invoices/{invoice}/payments/create', [PaymentController::class, 'create'])
            ->whereNumber('invoice')
            ->name('payments.create');
        Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])
            ->whereNumber('invoice')
            ->name('payments.store');

        // USR-01 / LOG-01 (管理者)
        Route::prefix('admin')->name('admin.')->group(function (): void {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])
                ->whereNumber('user')
                ->name('users.toggle');

            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });
    });
});

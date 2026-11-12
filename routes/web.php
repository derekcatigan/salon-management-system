<?php

use App\Enum\RoleEnum;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\CashierDashboardController;
use App\Http\Controllers\Dashboard\StaffDashboardController;
use App\Http\Controllers\Manage\AccountController;
use App\Http\Controllers\Manage\InventoryController;
use App\Http\Controllers\Manage\ServiceController;
use App\Http\Controllers\Teller\TellerController;
use App\Http\Controllers\Teller\TellerReportController;
use App\Http\Controllers\Website\HomeWebController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/panel', 'index')->name('login');
        Route::post('/login', 'authenticate')->name('auth.login');
        Route::get('/login/throttle', 'throttleStatus')->name('auth.throttle');
    });

    // Website Routes
    Route::controller(HomeWebController::class)->group(function () {
        Route::get('/', 'index')->name('web.home');
    });
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        $role = Auth::user()->role;

        return match ($role) {
            RoleEnum::Admin => redirect()->route('admin.dashboard.index'),
            RoleEnum::Cashier => redirect()->route('cashier.dashboard.index'),
            RoleEnum::Staff => redirect()->route('staff.dashboard.index'),
            default => redirect()->route('login'),
        };
    })->name('dashboard');

    // Logging out route
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/force-logout', function (Request $request) {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    });

    // Admin Access Routes
    Route::middleware('role:admin')->group(function () {
        // Admin Dashboard Controller
        Route::controller(AdminDashboardController::class)->group(function () {
            Route::get('/admin/dashboard', 'index')->name('admin.dashboard.index');
        });

        // Account Controller
        Route::controller(AccountController::class)->group(function () {
            Route::get('/manage/account', 'index')->name('manage.account');
            Route::get('/manage/account/search', 'search')->name('manage.account.search');
            Route::post('/manage/account', 'store')->name('manage.account.store');
            Route::put('/manage/account/{user}', 'update')->name('manage.account.update');
            Route::delete('/manage/account/{user}', 'destroy')->name('manage.account.destroy');
        });

        // Service Controller
        Route::controller(ServiceController::class)->group(function () {
            Route::get('/manage/services', 'index')->name('manage.service.index');
            Route::post('/manage/services', 'store')->name('manage.service.store');
            Route::put('/manage/services/{service}', 'update')->name('manage.service.update');
            Route::delete('/manage/services/{service}', 'destroy')->name('manage.service.destroy');
        });

        // Inventory Controller
        Route::controller(InventoryController::class)->group(function () {
            Route::post('/manage/inventory', 'store')->name('manage.inventory.store');
        });
    });

    // Cashier Access Routes
    Route::middleware('role:cashier')->group(function () {
        // Cashier Dashboard Controller
        Route::controller(CashierDashboardController::class)->group(function () {
            Route::get('/cashier/dashboard', 'index')->name('cashier.dashboard.index');
        });

        // Teller Controller
        Route::controller(TellerController::class)->group(function () {
            Route::get('/cashier/teller', 'index')->name('teller.index');
            Route::post('/cashier/teller/checkout', 'checkout')->name('teller.checkout');
            Route::get('/cashier/teller/receipt/{sale}', 'receipt')->name('teller.receipt');
            Route::get('/cashier/teller/receipt/{sale}/print', 'printReceipt')->name('teller.receipt.print');
            Route::post('/cashier/teller/denomination', 'denomination')->name('teller.denomination');
        });

        // Teller Report Controller
        Route::controller(TellerReportController::class)->group(function () {
            Route::get('/cashier/teller/report', 'index')->name('teller.report.index');
            Route::get('/cashier/teller/report/{businessDate}', 'show')->name('teller.report.show');
            Route::get('/cashier/teller/report/{businessDate}/print', 'print')->name('teller.report.print');
        });
    });

    // Staff Access Routes
    Route::middleware('role:staff')->group(function () {
        // Staff Dashboard Controller
        Route::controller(StaffDashboardController::class)->group(function () {
            Route::get('/staff/dashboard', 'index')->name('staff.dashboard.index');
        });
    });

    // Shared Route Access (Admin & Staff)
    Route::middleware('role:admin,staff')->group(function () {
        // Inventory Controller
        Route::controller(InventoryController::class)->group(function () {
            Route::get('/manage/inventory', 'index')->name('manage.inventory');
            Route::put('/manage/inventory/{product}', 'update')->name('manage.inventory.update');
            Route::delete('/manage/inventory/{product}', 'destroy')->name('manage.inventory.destroy');
            Route::post('/manage/inventory/{product}/adjust', 'adjustStock')->name('manage.inventory.adjust');
        });
    });

    // Shared Route Access
    Route::middleware('role:admin,cashier,staff')->group(function () {});
});

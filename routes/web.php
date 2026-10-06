<?php

use App\Enum\RoleEnum;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\CashierDashboardController;
use App\Http\Controllers\Dashboard\StaffDashboardController;
use App\Http\Controllers\Manage\AccountController;
use App\Http\Controllers\Manage\InventoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/', 'index')->name('login');
        Route::post('/login', 'authenticate')->name('auth.login');
        Route::get('/login/throttle', 'throttleStatus')->name('auth.throttle');
    });
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        return match (Auth::user()->role) {
            RoleEnum::Admin => redirect()->route('admin.dashboard.index'),
            RoleEnum::Cashier => redirect()->route('cashier.dashboard.index'),
            RoleEnum::Staff => redirect()->route('staff.dashboard.index'),
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

        // Inventory Controller
        Route::controller(InventoryController::class)->group(function () {
            Route::get('/manage/inventory', 'index')->name('manage.inventory');
        });
    });

    // Cashier Access Routes
    Route::middleware('role:cashier')->group(function () {
        // Cashier Dashboard Controller
        Route::controller(CashierDashboardController::class)->group(function () {
            Route::get('/cashier/dashboard', 'index')->name('cashier.dashboard.index');
        });
    });

    // Staff Access Routes
    Route::middleware('role:staff')->group(function () {
        // Staff Dashboard Controller
        Route::controller(StaffDashboardController::class)->group(function () {
            Route::get('/staff/dashboard', 'index')->name('staff.dashboard.index');
        });
    });
});

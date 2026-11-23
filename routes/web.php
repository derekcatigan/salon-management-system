<?php

use App\Enum\RoleEnum;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\Appointment\AppointmentHistoryController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\CashierDashboardController;
use App\Http\Controllers\Dashboard\StaffDashboardController;
use App\Http\Controllers\Manage\AccountController;
use App\Http\Controllers\Manage\BookingController;
use App\Http\Controllers\Manage\InventoryController;
use App\Http\Controllers\Manage\ScheduleController;
use App\Http\Controllers\Manage\ServiceController;
use App\Http\Controllers\Teller\TellerController;
use App\Http\Controllers\Teller\TellerReportController;
use App\Http\Controllers\Website\AuthWebController;
use App\Http\Controllers\Website\HomeWebController;
use App\Http\Controllers\Website\RegisterWebController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Website Routes
Route::controller(HomeWebController::class)->group(function () {
    Route::get('/', 'index')->name('web.home');
});

// Appointment Routes
Route::controller(AppointmentController::class)->group(function () {
    Route::get('/mi-bella-la-vida/book-appointment', 'index')->name('appointment.web.index');
});

Route::middleware('guest')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/panel', 'index')->name('login');
        Route::post('/login', 'authenticate')->name('auth.login');
        Route::get('/login/throttle', 'throttleStatus')->name('auth.throttle');
    });

    // Login Web Route
    Route::controller(AuthWebController::class)->group(function () {
        Route::get('/mi-bella-la-vida/login', 'index')->name('auth.web.index');
        Route::post('/mi-bella-la-vida/login', 'store')->name('auth.web.login');
    });

    // Register Web Routes
    Route::controller(RegisterWebController::class)->group(function () {
        Route::get('/mi-bella-la-vida/register', 'index')->name('register.web.index');
        Route::post('/mi-bella-la-vida/register', 'store')->name('register.web.store');
    });
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        $role = Auth::user()->role;

        return match ($role) {
            RoleEnum::Admin => redirect()->route('admin.dashboard.index'),
            RoleEnum::Staff => redirect()->route('staff.dashboard.index'),
            RoleEnum::Customer => redirect()->route('web.home'),
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

    // Customer logout (redirects to website login)
    Route::post('/mi-bella-la-vida/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->forget('url.intended');

        return redirect()->route('auth.web.index');
    })->name('auth.web.logout');

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

    // Staff Access Routes
    Route::middleware('role:staff')->group(function () {
        // Staff Dashboard Controller
        Route::controller(StaffDashboardController::class)->group(function () {
            Route::get('/staff/dashboard', 'index')->name('staff.dashboard.index');
        });

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

    // Shared Route Access (Admin & Staff)
    Route::middleware('role:admin,staff')->group(function () {
        // Inventory Controller
        Route::controller(InventoryController::class)->group(function () {
            Route::get('/manage/inventory', 'index')->name('manage.inventory');
            Route::put('/manage/inventory/{product}', 'update')->name('manage.inventory.update');
            Route::delete('/manage/inventory/{product}', 'destroy')->name('manage.inventory.destroy');
            Route::post('/manage/inventory/{product}/adjust', 'adjustStock')->name('manage.inventory.adjust');
        });

        // Manage Schedule Controller
        Route::controller(ScheduleController::class)->group(function () {
            Route::get('/manage/booking-schedule', 'index')->name('manage.schedule.index');
            Route::post('/manage/booking-schedule/toggle', 'toggle')->name('manage.schedule.toggle');
            Route::post('/manage/booking-schedule/bulk', 'bulk')->name('manage.schedule.bulk');
        });

        // Manage Booking Controller
        Route::controller(BookingController::class)->group(function () {
            Route::get('/manage/booking', 'index')->name('manage.booking.index');
            Route::patch('/manage/booking/{booking}/status', 'updateStatus')->name('manage.booking.status');
        });
    });

    // Website Appointment Booking Routes
    Route::middleware('role:customer')->group(function () {
        Route::post('/mi-bella-la-vida/book-appointment', [AppointmentController::class, 'store'])
            ->name('appointment.web.store');

        // Appointment History Controller
        Route::controller(AppointmentHistoryController::class)->group(function () {
            Route::get('/mi-bella-la-vida/view-appointment-history', 'index')->name('appointment.history.index');
        });

    });
});

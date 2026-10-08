<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Customer;

// ================= TRANG KHÁCH (công khai, không cần đăng nhập) =================
Route::get('/', [Customer\HomeController::class, 'index'])->name('home');
Route::get('/vehicles', [Customer\VehicleController::class, 'index'])->name('vehicles.index');
Route::get('/vehicles/{vehicle}', [Customer\VehicleController::class, 'show'])->name('vehicles.show');
Route::get('/stores', [Customer\StoreController::class, 'index'])->name('stores.index');

// Route mặc định của Breeze, giữ tạm vì menu Breeze đang dùng
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ================= KHU QUẢN LÝ (staff, admin) =================
// Lớp 1: auth (đã đăng nhập) -> role (đúng vai trò)
// Lớp 2: can:<quyền> cho từng chức năng
Route::middleware(['auth', 'role:staff,admin'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::redirect('/', '/admin/dashboard');

        // C1 - Dashboard vận hành
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])
            ->middleware('can:dashboard.view')
            ->name('dashboard');

        // C9 - Giám sát GPS
        Route::middleware('can:gps.monitor')->group(function () {
            Route::get('/gps-monitor', [Admin\MapMonitorController::class, 'index'])
                ->name('gps-monitor');

            Route::get('/gps-monitor/positions', [Admin\PositionController::class, 'index'])
                ->name('gps-monitor.positions');
        });

        // Các chức năng quản lý sau này (xe, cửa hàng, đơn thuê...) thêm vào đây
                // C3 - Quản lý cửa hàng (chỉ admin)
        Route::middleware('can:store.manage')->group(function () {
            Route::resource('stores', Admin\StoreController::class)->except(['show']);
        });

                // C2 - Quản lý xe (chỉ admin). Xóa tách quyền riêng: vehicle.delete
        Route::middleware('can:vehicle.manage')->group(function () {
            Route::resource('vehicles', Admin\VehicleController::class)->except(['show', 'destroy']);
        });
        Route::delete('/vehicles/{vehicle}', [Admin\VehicleController::class, 'destroy'])
            ->middleware('can:vehicle.delete')
            ->name('vehicles.destroy');

                // C6 - Giá thuê và khuyến mãi (chỉ admin)
        Route::middleware('can:pricing.manage')->group(function () {
            Route::get('/pricing', [Admin\PricingController::class, 'index'])->name('pricing');
            Route::put('/pricing/by-type', [Admin\PricingController::class, 'updateByType'])->name('pricing.by-type');
            Route::put('/pricing/vehicles', [Admin\PricingController::class, 'updateVehicles'])->name('pricing.vehicles');

            Route::resource('promotions', Admin\PromotionController::class)->except(['show']);
            Route::patch('/promotions/{promotion}/toggle', [Admin\PromotionController::class, 'toggle'])
                ->name('promotions.toggle');
        });
    });

require __DIR__.'/auth.php';
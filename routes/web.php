<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Customer;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ================= TRANG KHÁCH (công khai, không cần đăng nhập) =================
Route::get('/', [Customer\HomeController::class, 'index'])->name('home');
Route::get('/vehicles', [Customer\VehicleController::class, 'index'])->name('vehicles.index');
Route::get('/vehicles/{vehicle}', [Customer\VehicleController::class, 'show'])->name('vehicles.show');
Route::get('/stores', [Customer\StoreController::class, 'index'])->name('stores.index');
Route::get('/payment/vnpay/return', [\App\Http\Controllers\PaymentController::class, 'vnpayReturn'])
    ->name('payment.vnpay.return');
Route::get('/payment/vnpay/ipn', [\App\Http\Controllers\PaymentController::class, 'vnpayIpn'])
    ->name('payment.vnpay.ipn');
// ================= ĐẶT THUÊ (khách hàng đã đăng nhập) =================
// B2 - chỉ tài khoản khách hàng (quyền booking.create)
Route::middleware('auth')->group(function () {
    Route::middleware('can:booking.create')->group(function () {
        Route::get('/vehicles/{vehicle}/book', [Customer\BookingController::class, 'create'])->name('booking.create');
        Route::post('/vehicles/{vehicle}/book', [Customer\BookingController::class, 'store'])->name('booking.store');
        Route::post('/vehicles/{vehicle}/quote', [Customer\BookingController::class, 'quote'])
            ->middleware('throttle:60,1')
            ->name('booking.quote');
        Route::post('/payment/{order}/vnpay', [\App\Http\Controllers\PaymentController::class, 'vnpayCreate'])
            ->name('payment.vnpay.create');
    });

    // Xem và hủy đơn: kiểm tra bằng RentalOrderPolicy (chủ đơn)
    Route::get('/booking/{order}', [Customer\BookingController::class, 'show'])->name('booking.show');
    Route::post('/booking/{order}/cancel', [Customer\BookingController::class, 'cancel'])->name('booking.cancel');
});

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
// CHỈ đặt ở đây route của khu quản lý. Route của khách hàng đặt ở các phần phía trên.
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

        // C3 - Quản lý cửa hàng (chỉ admin)
        Route::middleware('can:store.manage')->group(function () {
            Route::resource('stores', Admin\StoreController::class)->except(['show']);
        });

        // C5: Quản lý đơn thuê
        Route::middleware('can:order.manage')
            ->prefix('orders')
            ->name('orders.')
            ->controller(Admin\OrderController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{order}', 'show')->name('show');
                Route::post('/{order}/approve', 'approve')->name('approve');
                Route::post('/{order}/pick-up', 'pickUp')->name('pick-up');
                Route::post('/{order}/extend', 'extend')->name('extend');
                Route::post('/{order}/complete', 'complete')->name('complete');
                Route::post('/{order}/cancel', 'cancel')->name('cancel');
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

        // Trang mẫu giao diện để soát chế độ sáng/tối, chỉ có trên máy dev
        if (app()->isLocal()) {
            Route::view('/ui', 'admin.ui.ui')->middleware('can:user.manage')->name('ui');
        }

        // Các chức năng quản lý sau này (C4 khách hàng, C5 đơn thuê, C7 nhân viên, C8 báo cáo) thêm vào đây
    });

require __DIR__.'/auth.php';
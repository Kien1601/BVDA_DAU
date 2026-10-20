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
Route::get('/payment/momo/return', [\App\Http\Controllers\PaymentController::class, 'momoReturn'])
    ->name('payment.momo.return');
Route::post('/payment/momo/ipn', [\App\Http\Controllers\PaymentController::class, 'momoIpn'])
    ->name('payment.momo.ipn');
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
        Route::post('/payment/{order}/momo', [\App\Http\Controllers\PaymentController::class, 'momoCreate'])
            ->name('payment.momo.create');
        Route::post('/payment/{order}/cash', [\App\Http\Controllers\PaymentController::class, 'chooseCash'])
            ->name('payment.cash');
    });

    // Xem và hủy đơn: kiểm tra bằng RentalOrderPolicy (chủ đơn)
    Route::get('/booking/{order}', [Customer\BookingController::class, 'show'])->name('booking.show');
    Route::post('/booking/{order}/cancel', [Customer\BookingController::class, 'cancel'])->name('booking.cancel');
        // B4 - Tài khoản khách: lịch sử đơn, hóa đơn
    Route::get('/account/orders', [Customer\AccountController::class, 'orders'])
        ->middleware('can:order.view_own')
        ->name('account.orders');
    Route::get('/account/orders/{order}/invoice', [Customer\AccountController::class, 'invoice'])
        ->name('account.invoice');
    Route::post('/bookings/{order}/early-return', [\App\Http\Controllers\EarlyReturnController::class, 'store'])
        ->name('booking.early-return');
    Route::delete('/bookings/{order}/early-return', [\App\Http\Controllers\EarlyReturnController::class, 'destroy'])
        ->name('booking.early-return.cancel');
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
                Route::post('/{order}/cash', 'collectCash')->name('collect-cash');
                Route::post('/{order}/refunds/{payment}', 'confirmRefund')->name('confirm-refund');
            });

        // C4: Khách hàng
        Route::middleware('can:customer.view')
            ->prefix('customers')
            ->name('customers.')
            ->controller(\App\Http\Controllers\Admin\CustomerController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{customer}', 'show')->name('show');
                Route::post('/{customer}/toggle-lock', 'toggleLock')
                    ->middleware('can:customer.lock')
                    ->name('toggle-lock');
                Route::post('/{customer}/violations', 'storeViolation')
                    ->middleware('can:violation.create')
                    ->name('violations.store');
                Route::delete('/{customer}/violations/{violation}', 'destroyViolation')
                    ->middleware('can:violation.delete')
                    ->name('violations.destroy');
            });
            Route::middleware('can:user.manage')
            ->prefix('staff')
            ->name('staff.')
            ->controller(\App\Http\Controllers\Admin\StaffController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{staff}/edit', 'edit')->name('edit');
        Route::put('/{staff}', 'update')->name('update');
        Route::post('/{staff}/toggle-lock', 'toggleLock')->name('toggle-lock');
    });
            // C7: Nhân viên
        Route::prefix('notifications')->name('notifications.')->controller(\App\Http\Controllers\Admin\NotificationController::class)
        ->group(function () {
            Route::get('/feed', 'feed')->name('feed');
            Route::get('/{id}/open', 'open')->name('open');
            Route::post('/read-all', 'readAll')->name('read-all');
            Route::get('/', 'index')->name('index');
        });
                // C8 - Báo cáo (chỉ admin)
        Route::middleware('can:report.view')
            ->prefix('reports')
            ->name('reports.')
            ->controller(Admin\ReportController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/export', 'export')->name('export');
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
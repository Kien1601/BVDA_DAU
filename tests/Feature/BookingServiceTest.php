<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\VehicleStatus;
use App\Models\Promotion;
use App\Models\RentalOrder;
use App\Models\Store;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $booking;
    private User $customer;
    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 08:00:00');   // cố định "bây giờ" để test luôn cho cùng kết quả

        $this->booking = app(BookingService::class);
        $this->customer = User::factory()->create();

        $store = Store::create(['name' => 'CH test', 'address' => 'Đà Nẵng', 'latitude' => 16.06, 'longitude' => 108.22]);
        $this->vehicle = Vehicle::create([
            'store_id' => $store->id, 'license_plate' => '43B1-000.01', 'name' => 'Xe test', 'type' => 'Tay ga',
            'price_per_hour' => 30000, 'price_per_day' => 150000, 'price_per_week' => 900000,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function t(string $value): Carbon
    {
        return Carbon::parse($value);
    }

    private function order(array $override = []): RentalOrder
    {
        return RentalOrder::create(array_merge([
            'user_id' => $this->customer->id, 'vehicle_id' => $this->vehicle->id,
            'start_time' => '2026-10-21 09:00', 'end_time' => '2026-10-21 12:00',
            'status' => OrderStatus::Approved,
            'subtotal_amount' => 90000, 'total_amount' => 90000, 'deposit_amount' => 100000,
        ], $override));
    }

    /** Gọi hàm, khẳng định bị từ chối đúng ở trường $field */
    private function assertRejected(callable $call, string $field): void
    {
        try {
            $call();
            $this->fail("Lẽ ra phải bị từ chối ở trường {$field}");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
        }
    }

    /* ---------------------------------------------------------- B2.2 */

    public function test_tinh_tien_theo_gio_ngay_tuan_co_tran(): void
    {
        $start = $this->t('2026-10-21 09:00');

        foreach ([
            [3, 90000],      // 3 giờ
            [6, 150000],     // 6 giờ = 180.000 > 1 ngày → lấy giá ngày
            [26, 210000],    // 1 ngày + 2 giờ
            [144, 900000],   // 6 ngày = giá 1 tuần
            [168, 900000],   // đúng 1 tuần
            [216, 1200000],  // 1 tuần + 2 ngày
        ] as [$hours, $expected]) {
            $amount = $this->booking->rentFor($this->vehicle, $start, $start->copy()->addHours($hours))['amount'];
            $this->assertSame($expected, $amount, "Thuê {$hours} giờ");
        }
    }

    public function test_le_phut_lam_tron_len_mot_gio(): void
    {
        $quote = $this->booking->rentFor($this->vehicle, $this->t('2026-10-21 09:00'), $this->t('2026-10-21 11:10'));

        $this->assertSame(3, $quote['hours']);
        $this->assertSame(90000, $quote['amount']);
    }

    public function test_tien_coc_30_phan_tram_lam_tron_len_toi_thieu_100_nghin(): void
    {
        $this->assertSame(100000, $this->booking->depositFor(90000));     // 27.000 < tối thiểu
        $this->assertSame(360000, $this->booking->depositFor(1200000));   // đúng 30%
        $this->assertSame(310000, $this->booking->depositFor(1010000));   // 303.000 → làm tròn lên 310.000
    }

    /* ---------------------------------------------------------- B2.4 */

    public function test_tao_don_cho_duyet_va_giu_cho_30_phut(): void
    {
        $order = $this->booking->create($this->customer, $this->vehicle,
            $this->t('2026-10-21 09:00'), $this->t('2026-10-21 12:00'));

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(90000, $order->total_amount);
        $this->assertSame(100000, $order->deposit_amount);
        $this->assertTrue($order->expires_at->equalTo(now()->addMinutes(30)));
    }

    /* ---------------------------------------------------------- B2.3 */

    public function test_ap_ma_khuyen_mai_khong_phan_biet_hoa_thuong(): void
    {
        $promo = Promotion::create([
            'code' => 'GIAM10', 'discount_percent' => 10,
            'start_date' => today(), 'end_date' => today()->addDays(5), 'is_active' => true,
        ]);

        $order = $this->booking->create($this->customer, $this->vehicle,
            $this->t('2026-10-21 09:00'), $this->t('2026-10-21 12:00'), 'giam10');

        $this->assertSame(9000, $order->discount_amount);
        $this->assertSame(81000, $order->total_amount);
        $this->assertSame($promo->id, $order->promotion_id);
    }

    public function test_ma_sai_hoac_het_han_bi_tu_choi(): void
    {
        Promotion::create([
            'code' => 'HETHAN', 'discount_percent' => 10,
            'start_date' => today()->subDays(9), 'end_date' => today()->subDay(), 'is_active' => true,
        ]);

        foreach (['HETHAN', 'KHONGCO'] as $code) {
            $this->assertRejected(fn () => $this->booking->quote($this->vehicle,
                $this->t('2026-10-21 09:00'), $this->t('2026-10-21 12:00'), $code), 'promotion_code');
        }
    }

    /* ---------------------------------------------------------- B2.1 */

    public function test_khong_cho_dat_trung_lich(): void
    {
        $this->order();   // đã duyệt 09:00–12:00

        $this->assertRejected(fn () => $this->booking->create($this->customer, $this->vehicle,
            $this->t('2026-10-21 11:00'), $this->t('2026-10-21 13:00')), 'start_time');

        $this->assertSame(1, RentalOrder::count());
    }

    public function test_dat_noi_tiep_khong_bi_tinh_la_trung(): void
    {
        $this->order();   // 09:00–12:00

        $order = $this->booking->create($this->customer, $this->vehicle,
            $this->t('2026-10-21 12:00'), $this->t('2026-10-21 14:00'));

        $this->assertSame(OrderStatus::Pending, $order->status);
    }

    public function test_don_qua_han_giu_cho_va_don_da_huy_khong_giu_xe(): void
    {
        $this->order(['status' => OrderStatus::Pending, 'expires_at' => now()->subMinute()]);
        $this->order(['status' => OrderStatus::Cancelled]);

        $order = $this->booking->create($this->customer, $this->vehicle,
            $this->t('2026-10-21 10:00'), $this->t('2026-10-21 11:00'));

        $this->assertSame(OrderStatus::Pending, $order->status);
    }

    public function test_xe_bao_duong_khong_dat_duoc(): void
    {
        $this->vehicle->update(['status' => VehicleStatus::Maintenance]);

        $this->assertRejected(fn () => $this->booking->quote($this->vehicle,
            $this->t('2026-10-21 09:00'), $this->t('2026-10-21 12:00')), 'vehicle');
    }

    public function test_kiem_tra_khoang_thoi_gian(): void
    {
        // trả trước khi nhận
        $this->assertRejected(fn () => $this->booking->quote($this->vehicle,
            $this->t('2026-10-21 12:00'), $this->t('2026-10-21 09:00')), 'end_time');

        // nhận xe sau 10 phút (yêu cầu tối thiểu 30 phút)
        $this->assertRejected(fn () => $this->booking->quote($this->vehicle,
            now()->addMinutes(10), now()->addHours(3)), 'start_time');

        // thuê 31 ngày
        $this->assertRejected(fn () => $this->booking->quote($this->vehicle,
            $this->t('2026-10-21 09:00'), $this->t('2026-11-21 09:00')), 'end_time');
    }

    /* ---------------------------------------------------------- giữ chỗ, hủy */

    public function test_lenh_huy_don_qua_han_giu_cho(): void
    {
        $expired = $this->order(['status' => OrderStatus::Pending, 'expires_at' => now()->subMinute()]);
        $holding = $this->order(['status' => OrderStatus::Pending, 'expires_at' => now()->addMinutes(10),
            'start_time' => '2026-10-22 09:00', 'end_time' => '2026-10-22 10:00']);
        $approved = $this->order(['start_time' => '2026-10-23 09:00', 'end_time' => '2026-10-23 10:00']);

        $this->artisan('rental:expire-pending')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, $expired->fresh()->status);
        $this->assertSame('Quá hạn giữ chỗ', $expired->fresh()->cancel_reason);
        $this->assertSame(OrderStatus::Pending, $holding->fresh()->status);
        $this->assertSame(OrderStatus::Approved, $approved->fresh()->status);
    }

    public function test_khach_huy_duoc_don_cho_duyet_nhung_khong_huy_duoc_don_dang_thue(): void
    {
        $pending = $this->order(['status' => OrderStatus::Pending, 'expires_at' => now()->addMinutes(30)]);
        $this->booking->cancelByCustomer($pending);
        $this->assertSame(OrderStatus::Cancelled, $pending->fresh()->status);

        $active = $this->order(['status' => OrderStatus::Active,
            'start_time' => '2026-10-22 09:00', 'end_time' => '2026-10-22 10:00']);
        $this->assertRejected(fn () => $this->booking->cancelByCustomer($active), 'order');
    }

    public function test_khong_xoa_duoc_xe_con_don_va_ma_da_dung(): void
    {
        $promo = Promotion::create([
            'code' => 'DADUNG', 'discount_percent' => 10,
            'start_date' => today(), 'end_date' => today()->addDays(5), 'is_active' => true,
        ]);
        $this->order(['promotion_id' => $promo->id]);

        $this->assertFalse($this->vehicle->canBeDeleted());

        $this->actingAs(User::factory()->admin()->create())
            ->delete("/admin/promotions/{$promo->id}")
            ->assertSessionHas('error');
        $this->assertDatabaseHas('promotions', ['id' => $promo->id]);
    }
}
<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\VehicleStatus;
use App\Models\RentalOrder;
use App\Models\Store;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->freezeTime();
    }

    private function taoNhanVien(): User
    {
        return User::factory()->create(['role' => 'staff']);
    }

    private function taoXe(array $attrs = []): Vehicle
    {
        static $soThuTu = 0;
        $soThuTu++;

        $store = Store::first() ?? Store::create(['name' => 'CH test', 'address' => 'Đà Nẵng', 'latitude' => 16.06, 'longitude' => 108.22]);

        return Vehicle::create(array_merge([
            'store_id' => $store->id, 'license_plate' => sprintf('43B1-%03d.%02d', $soThuTu, $soThuTu % 100),
            'name' => 'Xe test '.$soThuTu, 'type' => 'Tay ga',
            'price_per_hour' => 30000, 'price_per_day' => 150000, 'price_per_week' => 900000,
        ], $attrs));
    }

    private function taoDon(OrderStatus $status, array $attrs = [], ?Vehicle $xe = null): RentalOrder
    {
        $xe ??= $this->taoXe([
            'status' => $status === OrderStatus::Active ? 'rented' : 'available',
        ]);

        return RentalOrder::create(array_merge([
            'user_id' => User::factory()->create(['role' => 'customer'])->id,
            'vehicle_id' => $xe->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(25),
            'status' => $status,
            'subtotal_amount' => 200000,
            'discount_amount' => 0,
            'total_amount' => 200000,
            'deposit_amount' => 100000,
            'surcharge_amount' => 0,
            'expires_at' => $status === OrderStatus::Pending ? now()->addMinutes(30) : null,
        ], $attrs));
    }

    public function test_khach_hang_khong_vao_duoc_trang_quan_ly_don(): void
    {
        $khach = User::factory()->create(['role' => 'customer']);

        $this->actingAs($khach)->get(route('admin.orders.index'))->assertForbidden();
    }

    public function test_nhan_vien_xem_danh_sach_va_loc_theo_trang_thai(): void
    {
        $choDuyet = $this->taoDon(OrderStatus::Pending);
        $daHuy = $this->taoDon(OrderStatus::Cancelled);

        $this->actingAs($this->taoNhanVien())
            ->get(route('admin.orders.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('#'.$choDuyet->id)
            ->assertDontSee('#'.$daHuy->id);
    }

    public function test_nhan_vien_xem_chi_tiet_don(): void
    {
        $don = $this->taoDon(OrderStatus::Pending);

        $this->actingAs($this->taoNhanVien())
            ->get(route('admin.orders.show', $don))
            ->assertOk()
            ->assertSee($don->user->name);
    }

    public function test_duyet_don_cho_duyet(): void
    {
        $nhanVien = $this->taoNhanVien();
        $don = $this->taoDon(OrderStatus::Pending);

        $this->actingAs($nhanVien)->post(route('admin.orders.approve', $don))->assertSessionHasNoErrors();

        $don->refresh();
        $this->assertSame(OrderStatus::Approved, $don->status);
        $this->assertEquals($nhanVien->id, $don->approved_by);
        $this->assertNull($don->expires_at);
    }

    public function test_khong_duyet_don_da_het_giu_cho(): void
    {
        $don = $this->taoDon(OrderStatus::Pending, ['expires_at' => now()->subMinute()]);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.approve', $don))
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Pending, $don->refresh()->status);
    }

    public function test_giao_xe_chuyen_don_dang_thue_va_xe_dang_cho_thue(): void
    {
        $don = $this->taoDon(OrderStatus::Approved, ['start_time' => now()->addMinutes(30)]);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.pick-up', $don))
            ->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Active, $don->refresh()->status);
        $this->assertNotNull($don->picked_up_at);
        $this->assertSame(VehicleStatus::Rented, $don->vehicle->refresh()->status);
    }

    public function test_khong_giao_xe_qua_som(): void
    {
        $don = $this->taoDon(OrderStatus::Approved, ['start_time' => now()->addHours(3)]);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.pick-up', $don))
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Approved, $don->refresh()->status);
    }

    public function test_khong_giao_xe_khi_xe_dang_bao_tri(): void
    {
        $xe = $this->taoXe(['status' => 'maintenance']);
        $don = $this->taoDon(OrderStatus::Approved, ['start_time' => now()->addMinutes(10)], $xe);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.pick-up', $don))
            ->assertSessionHasErrors('status');
    }

    public function test_gia_han_cap_nhat_gio_tra_va_tien(): void
    {
        $don = $this->taoDon(OrderStatus::Active, [
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(3),
        ]);
        $gioTraCu = $don->end_time->copy();
        $gioTraMoi = now()->addHours(27)->startOfMinute();

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.extend', $don), ['end_time' => $gioTraMoi->format('Y-m-d\TH:i')])
            ->assertSessionHasNoErrors();

        $don->refresh();
        $tienMoi = app(BookingService::class)->rentFor($don->vehicle, $don->start_time, $gioTraMoi)['amount'];
        $this->assertTrue($don->end_time->equalTo($gioTraMoi));
        $this->assertTrue($don->original_end_time->equalTo($gioTraCu));
        $this->assertEquals($tienMoi, $don->subtotal_amount);
    }

    public function test_gia_han_bi_chan_khi_trung_don_khac(): void
    {
        $don = $this->taoDon(OrderStatus::Active, [
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(3),
        ]);
        $this->taoDon(OrderStatus::Approved, [
            'start_time' => now()->addHours(5),
            'end_time' => now()->addHours(8),
        ], $don->vehicle);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.extend', $don), ['end_time' => now()->addHours(6)->format('Y-m-d\TH:i')])
            ->assertSessionHasErrors();

        $this->assertNull($don->refresh()->original_end_time);
    }

    public function test_tra_xe_dung_gio_khong_phu_thu(): void
    {
        $don = $this->taoDon(OrderStatus::Active, [
            'start_time' => now()->subDay(),
            'end_time' => now()->addMinutes(5),
        ]);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.complete', $don))
            ->assertSessionHasNoErrors();

        $don->refresh();
        $this->assertSame(OrderStatus::Completed, $don->status);
        $this->assertEquals(0, $don->surcharge_amount);
        $this->assertNotNull($don->actual_return_time);
        $this->assertSame(VehicleStatus::Available, $don->vehicle->refresh()->status);
    }

    public function test_tra_xe_tre_tinh_phu_thu(): void
    {
        $don = $this->taoDon(OrderStatus::Active, [
            'start_time' => now()->subDay(),
            'end_time' => now()->subHours(2),
        ]);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.complete', $don), ['extra_fee' => 50000, 'surcharge_note' => 'Trầy yếm'])
            ->assertSessionHasNoErrors();

        $don->refresh();
        $phiTre = app(BookingService::class)->rentFor($don->vehicle, $don->end_time, now())['amount'];
        $this->assertGreaterThan(0, $phiTre);
        $this->assertEquals($phiTre + 50000, $don->surcharge_amount);
    }

    public function test_phu_thu_khac_bat_buoc_ghi_ly_do(): void
    {
        $don = $this->taoDon(OrderStatus::Active, ['start_time' => now()->subDay(), 'end_time' => now()->addHour()]);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.complete', $don), ['extra_fee' => 50000])
            ->assertSessionHasErrors('surcharge_note');

        $this->assertSame(OrderStatus::Active, $don->refresh()->status);
    }

    public function test_huy_don_bat_buoc_ly_do(): void
    {
        $don = $this->taoDon(OrderStatus::Approved);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.cancel', $don))
            ->assertSessionHasErrors('cancel_reason');

        $this->assertSame(OrderStatus::Approved, $don->refresh()->status);
    }

    public function test_nhan_vien_huy_don_da_duyet(): void
    {
        $nhanVien = $this->taoNhanVien();
        $don = $this->taoDon(OrderStatus::Approved);

        $this->actingAs($nhanVien)
            ->post(route('admin.orders.cancel', $don), ['cancel_reason' => 'Khách không đến'])
            ->assertSessionHasNoErrors();

        $don->refresh();
        $this->assertSame(OrderStatus::Cancelled, $don->status);
        $this->assertEquals($nhanVien->id, $don->cancelled_by);
        $this->assertSame('Khách không đến', $don->cancel_reason);
    }

    public function test_khong_huy_don_dang_thue(): void
    {
        $don = $this->taoDon(OrderStatus::Active, ['start_time' => now()->subHour()]);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.cancel', $don), ['cancel_reason' => 'Thử'])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Active, $don->refresh()->status);
    }
}
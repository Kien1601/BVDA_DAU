<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use App\Models\RentalOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->freezeTime();
    }

    private function taoKhach(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function taoNhanVien(): User
    {
        return User::factory()->create(['role' => 'staff']);
    }

    private function taoDon(OrderStatus $status, ?User $khach = null, array $attrs = []): RentalOrder
    {
        $xe = Vehicle::factory()->create([
            'status' => $status === OrderStatus::Active ? 'rented' : 'available',
        ]);

        $daXong = $status === OrderStatus::Completed;

        return RentalOrder::create(array_merge([
            'user_id' => ($khach ?? $this->taoKhach())->id,
            'vehicle_id' => $xe->id,
            'start_time' => $daXong ? now()->subDay() : now()->addHour(),
            'end_time' => $daXong ? now()->subHour() : now()->addHours(25),
            'actual_return_time' => $daXong ? now()->subHour() : null,
            'picked_up_at' => $daXong ? now()->subDay() : null,
            'status' => $status,
            'subtotal_amount' => 200000,
            'discount_amount' => 0,
            'total_amount' => 200000,
            'deposit_amount' => 100000,
            'surcharge_amount' => 0,
            'expires_at' => $status === OrderStatus::Pending ? now()->addMinutes(30) : null,
        ], $attrs));
    }

    /** Thu đủ tiền cho đơn: cọc + phần còn lại */
    private function thuDu(RentalOrder $don): void
    {
        $service = app(PaymentService::class);
        $nhanVien = $this->taoNhanVien();
        $service->collectCash($don, PaymentType::Deposit, $don->deposit_amount, $nhanVien);
        $service->collectCash($don, PaymentType::Balance, $service->amountDue($don->refresh()), $nhanVien);
    }

    public function test_khach_chi_thay_don_cua_minh(): void
    {
        $khach = $this->taoKhach();
        $donCuaMinh = $this->taoDon(OrderStatus::Pending, $khach);
        $donNguoiKhac = $this->taoDon(OrderStatus::Pending);

        $this->actingAs($khach)
            ->get(route('account.orders'))
            ->assertOk()
            ->assertSee('Đơn #'.$donCuaMinh->id)
            ->assertDontSee('Đơn #'.$donNguoiKhac->id);
    }

    public function test_loc_don_theo_trang_thai(): void
    {
        $khach = $this->taoKhach();
        $donCho = $this->taoDon(OrderStatus::Pending, $khach);
        $donXong = $this->taoDon(OrderStatus::Completed, $khach);

        $this->actingAs($khach)
            ->get(route('account.orders', ['status' => 'completed']))
            ->assertOk()
            ->assertSee('Đơn #'.$donXong->id)
            ->assertDontSee('Đơn #'.$donCho->id);
    }

    public function test_trang_don_cua_toi_khong_lo_du_lieu_gps(): void
    {
        $khach = $this->taoKhach();
        $don = $this->taoDon(OrderStatus::Active, $khach);
        $don->vehicle->forceFill(['gps_device_id' => '860000000000123', 'last_lat' => 16.0544563])->save();

        $this->actingAs($khach)
            ->get(route('account.orders'))
            ->assertOk()
            ->assertDontSee('860000000000123')
            ->assertDontSee('16.0544563');
    }

    public function test_chua_dang_nhap_bi_chuyen_ve_trang_dang_nhap(): void
    {
        $this->get(route('account.orders'))->assertRedirect(route('login'));
    }

    public function test_nhan_vien_khong_vao_trang_don_cua_toi(): void
    {
        $this->actingAs($this->taoNhanVien())
            ->get(route('account.orders'))
            ->assertForbidden();
    }

    public function test_trang_don_hien_hanh_trinh_va_link_hoa_don(): void
    {
        $khach = $this->taoKhach();
        $don = $this->taoDon(OrderStatus::Completed, $khach);

        $this->actingAs($khach)
            ->get(route('booking.show', $don))
            ->assertOk()
            ->assertSee('Hành trình đơn')
            ->assertSee(route('account.invoice', $don));
    }

    public function test_don_chua_xong_khong_co_link_hoa_don(): void
    {
        $khach = $this->taoKhach();
        $don = $this->taoDon(OrderStatus::Approved, $khach);

        $this->actingAs($khach)
            ->get(route('booking.show', $don))
            ->assertOk()
            ->assertDontSee(route('account.invoice', $don));
    }

    public function test_khach_xem_hoa_don_da_thanh_toan_du(): void
    {
        $khach = $this->taoKhach();
        $don = $this->taoDon(OrderStatus::Completed, $khach, [
            'surcharge_amount' => 50000,
            'surcharge_note' => 'Trả trễ',
        ]);
        $this->thuDu($don);

        $this->actingAs($khach)
            ->get(route('account.invoice', $don))
            ->assertOk()
            ->assertSee('HD'.str_pad((string) $don->id, 6, '0', STR_PAD_LEFT))
            ->assertSee('Trả trễ')
            ->assertSee('250.000 đ')
            ->assertSee('Đã thanh toán đủ');
    }

    public function test_hoa_don_bao_con_no_khi_chua_thu_du(): void
    {
        $khach = $this->taoKhach();
        $don = $this->taoDon(OrderStatus::Completed, $khach);
        app(PaymentService::class)->collectCash($don, PaymentType::Deposit, 100000, $this->taoNhanVien());

        $this->actingAs($khach)
            ->get(route('account.invoice', $don))
            ->assertOk()
            ->assertSee('Chưa thanh toán đủ');
    }

    public function test_don_chua_hoan_thanh_khong_co_hoa_don(): void
    {
        $khach = $this->taoKhach();
        $don = $this->taoDon(OrderStatus::Active, $khach);

        $this->actingAs($khach)
            ->get(route('account.invoice', $don))
            ->assertNotFound();
    }

    public function test_khach_khac_khong_xem_duoc_hoa_don(): void
    {
        $don = $this->taoDon(OrderStatus::Completed);

        $this->actingAs($this->taoKhach())
            ->get(route('account.invoice', $don))
            ->assertForbidden();
    }

    public function test_nhan_vien_xem_duoc_hoa_don_de_in_cho_khach(): void
    {
        $don = $this->taoDon(OrderStatus::Completed);

        $this->actingAs($this->taoNhanVien())
            ->get(route('account.invoice', $don))
            ->assertOk();
    }
}
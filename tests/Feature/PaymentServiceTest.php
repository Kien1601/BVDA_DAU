<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\RentalOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
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

    private function taoDon(OrderStatus $status, array $attrs = []): RentalOrder
    {
        $xe = Vehicle::factory()->create([
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

    private function service(): PaymentService
    {
        return app(PaymentService::class);
    }

    public function test_khach_chon_tra_tai_cua_hang_thi_bo_han_giu_cho(): void
    {
        $don = $this->taoDon(OrderStatus::Pending);

        $this->actingAs($don->user)
            ->post(route('payment.cash', $don))
            ->assertSessionHasNoErrors();

        $don->refresh();
        $this->assertNull($don->expires_at);
        $coc = $don->payments()->first();
        $this->assertSame(PaymentType::Deposit, $coc->type);
        $this->assertSame(PaymentMethod::Cash, $coc->method);
        $this->assertSame(PaymentStatus::Pending, $coc->status);
    }

    public function test_khach_khac_khong_tra_coc_thay_duoc(): void
    {
        $don = $this->taoDon(OrderStatus::Pending);
        $khachKhac = User::factory()->create(['role' => 'customer']);

        $this->actingAs($khachKhac)->post(route('payment.cash', $don))->assertForbidden();
    }

    public function test_khong_tra_coc_lan_hai(): void
    {
        $don = $this->taoDon(OrderStatus::Approved);
        $this->service()->collectCash($don, PaymentType::Deposit, 100000, $this->taoNhanVien());

        $this->actingAs($don->user)
            ->post(route('payment.cash', $don))
            ->assertSessionHasErrors('payment');
    }

    public function test_ghi_nhan_thanh_toan_hai_lan_khong_cong_don(): void
    {
        $don = $this->taoDon(OrderStatus::Pending);
        $coc = $this->service()->startOnlineDeposit($don, PaymentMethod::Vnpay);

        $this->service()->markPaid($coc, 'GD1');
        $this->service()->markPaid($coc, 'GD1');

        $this->assertSame(100000, $don->refresh()->paidAmount());
        $this->assertNull($don->expires_at);
    }

    public function test_tra_online_thi_khoan_tien_mat_dang_cho_bi_huy(): void
    {
        $don = $this->taoDon(OrderStatus::Pending);
        $tienMat = $this->service()->chooseCashAtStore($don);
        $online = $this->service()->startOnlineDeposit($don, PaymentMethod::Momo);

        $this->service()->markPaid($online, 'MM1');

        $this->assertSame(PaymentStatus::Cancelled, $tienMat->refresh()->status);
    }

    public function test_tien_ve_sau_khi_don_da_huy_thi_tao_hoan_tien(): void
    {
        $don = $this->taoDon(OrderStatus::Pending);
        $coc = $this->service()->startOnlineDeposit($don, PaymentMethod::Vnpay);
        $don->update(['status' => OrderStatus::Cancelled]);

        $this->service()->markPaid($coc, 'GD2');

        $hoan = $don->payments()->where('type', PaymentType::Refund->value)->first();
        $this->assertNotNull($hoan);
        $this->assertSame(100000, $hoan->amount);
        $this->assertSame(PaymentStatus::Pending, $hoan->status);
    }

    public function test_nhan_vien_thu_coc_tien_mat(): void
    {
        $nhanVien = $this->taoNhanVien();
        $don = $this->taoDon(OrderStatus::Approved);

        $this->actingAs($nhanVien)
            ->post(route('admin.orders.collect-cash', $don), ['type' => 'deposit'])
            ->assertSessionHasNoErrors();

        $coc = $don->payments()->first();
        $this->assertSame(PaymentStatus::Paid, $coc->status);
        $this->assertSame(100000, $coc->amount);
        $this->assertEquals($nhanVien->id, $coc->created_by);
        $this->assertTrue($don->depositPaid());
    }

    public function test_thu_phan_con_lai_gom_ca_phu_thu(): void
    {
        $nhanVien = $this->taoNhanVien();
        $don = $this->taoDon(OrderStatus::Completed, ['surcharge_amount' => 50000]);
        $this->service()->collectCash($don, PaymentType::Deposit, 100000, $nhanVien);

        $this->assertSame(150000, $this->service()->amountDue($don));

        $this->actingAs($nhanVien)
            ->post(route('admin.orders.collect-cash', $don), ['type' => 'balance'])
            ->assertSessionHasNoErrors();

        $conLai = $don->payments()->where('type', PaymentType::Balance->value)->first();
        $this->assertSame(150000, $conLai->amount);
        $this->assertSame(0, $this->service()->amountDue($don->refresh()));
    }

    public function test_huy_don_da_tra_coc_tao_hoan_tien_va_xac_nhan_duoc(): void
    {
        $nhanVien = $this->taoNhanVien();
        $don = $this->taoDon(OrderStatus::Approved);
        $this->service()->collectCash($don, PaymentType::Deposit, 100000, $nhanVien);

        $this->actingAs($nhanVien)
            ->post(route('admin.orders.cancel', $don), ['cancel_reason' => 'Khách đổi lịch'])
            ->assertSessionHasNoErrors();

        $hoan = $don->payments()->where('type', PaymentType::Refund->value)->first();
        $this->assertSame(100000, $hoan->amount);
        $this->assertSame(PaymentStatus::Pending, $hoan->status);

        $this->actingAs($nhanVien)
            ->post(route('admin.orders.confirm-refund', [$don, $hoan]))
            ->assertSessionHasNoErrors();

        $this->assertSame(PaymentStatus::Paid, $hoan->refresh()->status);
        $this->assertSame(0, $don->refresh()->paidAmount());
    }

    public function test_huy_don_chua_tra_tien_khong_tao_hoan_tien(): void
    {
        $don = $this->taoDon(OrderStatus::Pending);
        $tienMat = $this->service()->chooseCashAtStore($don);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.cancel', $don), ['cancel_reason' => 'Khách không đến'])
            ->assertSessionHasNoErrors();

        $this->assertSame(PaymentStatus::Cancelled, $tienMat->refresh()->status);
        $this->assertFalse($don->payments()->where('type', PaymentType::Refund->value)->exists());
    }
}
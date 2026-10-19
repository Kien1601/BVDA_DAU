<?php

namespace Tests\Feature;

use App\Enums\AlertKind;
use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use App\Models\RentalOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\OrderAlertService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarlyReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $nhanVien;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->withoutVite();

        $this->nhanVien = User::factory()->create(['role' => 'staff']);
    }

    /** Đơn đang thuê: nhận 1 giờ trước, hẹn trả sau 23 giờ, giá đúng bằng rentFor */
    private function taoDon(OrderStatus $status = OrderStatus::Active): RentalOrder
    {
        $xe = Vehicle::factory()->create([
            'status' => $status === OrderStatus::Active ? 'rented' : 'available',
            'price_per_hour' => 50000,
            'price_per_day' => 300000,
            'price_per_week' => 1500000,
        ]);
        $start = now()->subHour();
        $end = now()->addHours(23);
        $gia = app(BookingService::class)->rentFor($xe, $start, $end)['amount'];

        return RentalOrder::create([
            'user_id' => User::factory()->create(['role' => 'customer'])->id,
            'vehicle_id' => $xe->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
            'subtotal_amount' => $gia,
            'discount_amount' => 0,
            'total_amount' => $gia,
            'deposit_amount' => 100000,
            'surcharge_amount' => 0,
        ]);
    }

    private function baoTraSom(RentalOrder $don, $luc, ?User $khach = null)
    {
        return $this->actingAs($khach ?? $don->user)->post(route('booking.early-return', $don), [
            'early_return_at' => $luc->format('Y-m-d\TH:i'),
            'early_return_note' => 'Tầm 40 phút nữa tôi tới',
        ]);
    }

    public function test_khach_bao_tra_som_nhan_vien_nhan_thong_bao_va_thay_banner(): void
    {
        $don = $this->taoDon();

        $this->baoTraSom($don, now()->addHours(2))->assertSessionHasNoErrors();

        $this->assertSame(AlertKind::EarlyReturn->value, $this->nhanVien->notifications()->first()->data['kind']);
        $this->actingAs($this->nhanVien)->get(route('admin.orders.show', $don))->assertSee('Khách báo trả sớm lúc');
    }

    public function test_gio_bao_qua_gan_hoac_sau_gio_hen_bi_tu_choi(): void
    {
        $don = $this->taoDon();

        $this->baoTraSom($don, now()->addMinutes(10))->assertSessionHasErrors('early_return_at');
        $this->baoTraSom($don, $don->end_time->copy()->addHour())->assertSessionHasErrors('early_return_at');

        $this->assertNull($don->fresh()->early_return_at);
    }

    public function test_khach_khac_khong_bao_duoc(): void
    {
        $don = $this->taoDon();
        $khac = User::factory()->create(['role' => 'customer']);

        $this->baoTraSom($don, now()->addHours(2), $khac)->assertForbidden();
    }

    public function test_don_chua_giao_xe_khong_bao_duoc(): void
    {
        $don = $this->taoDon(OrderStatus::Approved);

        $this->baoTraSom($don, now()->addHours(2))->assertSessionHasErrors('early_return_at');
    }

    public function test_huy_bao_roi_bao_lai_van_thong_bao(): void
    {
        $don = $this->taoDon();
        $luc = now()->addHours(2);

        $this->baoTraSom($don, $luc);
        $this->actingAs($don->user)->delete(route('booking.early-return.cancel', $don))->assertSessionHasNoErrors();
        $this->assertNull($don->fresh()->early_return_at);

        $this->baoTraSom($don, $luc);

        $this->assertSame(2, $this->nhanVien->notifications()->count());
    }

    public function test_nhac_sap_tra_theo_gio_khach_bao(): void
    {
        $don = $this->taoDon();
        $this->baoTraSom($don, now()->addHours(2));

        $this->travel(61)->minutes();
        app(OrderAlertService::class)->scan();

        $this->assertTrue($this->nhanVien->notifications->contains(
            fn ($n) => $n->data['kind'] === AlertKind::ReturnSoon->value
        ));
    }

    public function test_tra_som_co_bao_truoc_tinh_lai_tien_va_hoan_phan_du(): void
    {
        $don = $this->taoDon();
        $giaCu = $don->total_amount;
        app(PaymentService::class)->collectCash($don, PaymentType::Deposit, $giaCu, $this->nhanVien);
        $this->baoTraSom($don, now()->addHours(2));

        $this->travel(2)->hours();
        $this->actingAs($this->nhanVien)->post(route('admin.orders.complete', $don), ['extra_fee' => 0])
            ->assertSessionHasNoErrors();

        $giaMoi = app(BookingService::class)->rentFor($don->vehicle, $don->start_time, now())['amount'];
        $don->refresh();
        $this->assertLessThan($giaCu, $giaMoi);
        $this->assertSame($giaMoi, (int) $don->total_amount);
        $this->assertSame($giaCu - $giaMoi, (int) $don->payments()->where('type', PaymentType::Refund->value)->value('amount'));
    }

    public function test_tra_som_khong_bao_truoc_giu_nguyen_gia(): void
    {
        $don = $this->taoDon();
        $giaCu = $don->total_amount;
        app(PaymentService::class)->collectCash($don, PaymentType::Deposit, $giaCu, $this->nhanVien);

        $this->travel(2)->hours();
        $this->actingAs($this->nhanVien)->post(route('admin.orders.complete', $don), ['extra_fee' => 0]);

        $this->assertSame((int) $giaCu, (int) $don->fresh()->total_amount);
        $this->assertSame(0, $don->payments()->where('type', PaymentType::Refund->value)->count());
    }
}
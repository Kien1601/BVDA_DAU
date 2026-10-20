<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\RentalOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReportService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->freezeTime();
    }

    private function taoAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function taoNhanVien(): User
    {
        return User::factory()->create(['role' => 'staff']);
    }

    private function taoDon(OrderStatus $status, ?Vehicle $xe = null, array $attrs = []): RentalOrder
    {
        $xe ??= Vehicle::factory()->create(['status' => 'available']);

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

    private function taoThanhToan(RentalOrder $don, PaymentType $type, int $amount, ?CarbonInterface $paidAt, PaymentStatus $status = PaymentStatus::Paid): Payment
    {
        return $don->payments()->create([
            'type' => $type,
            'method' => PaymentMethod::Cash,
            'amount' => $amount,
            'status' => $status,
            'txn_ref' => (string) Str::uuid(),
            'paid_at' => $status === PaymentStatus::Paid ? $paidAt : null,
        ]);
    }

    private function service(): ReportService
    {
        return app(ReportService::class);
    }

    public function test_doanh_thu_la_tien_da_thu_tru_tien_hoan(): void
    {
        $don = $this->taoDon(OrderStatus::Completed);
        $this->taoThanhToan($don, PaymentType::Deposit, 100000, now());
        $this->taoThanhToan($don, PaymentType::Balance, 150000, now()->subDays(3));
        $this->taoThanhToan($don, PaymentType::Refund, 50000, now());
        // Không tính: khoản chưa trả, và khoản trả ngoài kỳ
        $this->taoThanhToan($don, PaymentType::Balance, 999000, null, PaymentStatus::Pending);
        $this->taoThanhToan($don, PaymentType::Deposit, 777000, now()->subDays(40));

        $this->assertSame(200000, $this->service()->revenue(today()->subDays(29), today()));
    }

    public function test_doanh_thu_theo_ngay_du_moi_ngay_trong_ky(): void
    {
        $don = $this->taoDon(OrderStatus::Completed);
        $this->taoThanhToan($don, PaymentType::Deposit, 100000, now()->subDays(2));

        $ngay = $this->service()->revenueByDay(today()->subDays(6), today());

        $this->assertCount(7, $ngay);
        $this->assertSame(100000, $ngay[today()->subDays(2)->toDateString()]);
        $this->assertSame(0, $ngay[today()->toDateString()]);
    }

    public function test_dem_don_moi_va_luot_thue_trong_ky(): void
    {
        $this->taoDon(OrderStatus::Pending);
        $this->taoDon(OrderStatus::Cancelled);
        $this->taoDon(OrderStatus::Active, null, ['picked_up_at' => now()]);
        $donCu = $this->taoDon(OrderStatus::Completed, null, ['picked_up_at' => now()->subDays(40)]);
        $donCu->forceFill(['created_at' => now()->subDays(40)])->save();

        $thongKe = $this->service()->orderStats(today()->subDays(29), today());

        $this->assertSame(3, $thongKe['newOrders']);
        $this->assertSame(1, $thongKe['rentals']);
        $this->assertEquals(1, $thongKe['byStatus']['cancelled']);
    }

    public function test_xe_thue_nhieu_xep_theo_so_luot(): void
    {
        $xeA = Vehicle::factory()->create(['status' => 'available']);
        $xeB = Vehicle::factory()->create(['status' => 'available']);
        $this->taoDon(OrderStatus::Completed, $xeB, ['picked_up_at' => now()->subDay()]);
        $this->taoDon(OrderStatus::Completed, $xeA, ['picked_up_at' => now()->subDays(2)]);
        $this->taoDon(OrderStatus::Completed, $xeA, ['picked_up_at' => now()->subDays(5)]);

        $top = $this->service()->topVehicles(today()->subDays(29), today());

        $this->assertEquals($xeA->id, $top[0]->id);
        $this->assertEquals(2, $top[0]->rentals);
        $this->assertEquals($xeB->id, $top[1]->id);
    }

    public function test_admin_xem_duoc_trang_bao_cao(): void
    {
        $this->actingAs($this->taoAdmin())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Doanh thu thực thu');
    }

        public function test_nhan_vien_xem_duoc_bao_cao_khach_thi_khong(): void
    {
        $nhanVien = $this->taoNhanVien();

        $this->actingAs($nhanVien)->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($nhanVien)->get(route('admin.reports.export'))->assertOk();

        $khach = User::factory()->create(['role' => 'customer']);
        $this->actingAs($khach)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_ky_nhap_nguoc_hoac_qua_dai_tu_sua(): void
    {
        $admin = $this->taoAdmin();

        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['from' => today()->toDateString(), 'to' => today()->subDays(5)->toDateString()]))
            ->assertOk()
            ->assertViewHas('from', fn ($from) => $from->isSameDay(today()->subDays(5)));

        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['from' => today()->subDays(200)->toDateString(), 'to' => today()->toDateString()]))
            ->assertOk()
            ->assertViewHas('from', fn ($from) => $from->isSameDay(today()->subDays(91)));
    }

    public function test_xuat_csv_dung_noi_dung(): void
    {
        $don = $this->taoDon(OrderStatus::Completed);
        $this->taoThanhToan($don, PaymentType::Deposit, 123000, now());

        $response = $this->actingAs($this->taoAdmin())->get(route('admin.reports.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $noiDung = $response->streamedContent();
        $this->assertStringContainsString('DOANH THU THEO NGÀY', $noiDung);
        $this->assertStringContainsString('123000', $noiDung);
    }

    public function test_dashboard_admin_va_nhan_vien_deu_thay_doanh_thu(): void
    {
        $don = $this->taoDon(OrderStatus::Completed);
        $this->taoThanhToan($don, PaymentType::Deposit, 100000, now());

        $this->actingAs($this->taoAdmin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Doanh thu hôm nay')
            ->assertSee('100.000 đ');

        $this->actingAs($this->taoNhanVien())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Doanh thu hôm nay');
    }
}
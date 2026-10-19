<?php

namespace Tests\Feature;

use App\Enums\AlertKind;
use App\Enums\OrderStatus;
use App\Models\RentalOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\OrderAlert;
use App\Services\BookingService;
use App\Services\OrderAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $nhanVien;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->withoutVite();

        $this->nhanVien = User::factory()->create(['role' => 'staff']);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function quet(): int
    {
        return app(OrderAlertService::class)->scan();
    }

    private function bao(User $user, AlertKind $kind, RentalOrder $don): string
    {
        $user->notify(new OrderAlert($don, $kind));

        return $user->notifications()->first()->id;
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

    // ---------- Quét định kỳ ----------

    public function test_sap_giao_xe_bao_nhan_vien_va_admin_khong_bao_khach(): void
    {
        $don = $this->taoDon(OrderStatus::Approved, ['start_time' => now()->addMinutes(30)]);

        $this->assertSame(1, $this->quet());
        $this->assertSame(AlertKind::PickupSoon->value, $this->nhanVien->notifications()->first()->data['kind']);
        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertSame(0, $don->user->notifications()->count());
    }

    public function test_quet_lai_khong_gui_trung(): void
    {
        $this->taoDon(OrderStatus::Approved, ['start_time' => now()->addMinutes(30)]);

        $this->quet();

        $this->assertSame(0, $this->quet());
        $this->assertSame(1, $this->nhanVien->notifications()->count());
    }

    public function test_gia_han_thi_nhac_sap_tra_lai_theo_gio_moi(): void
    {
        $don = $this->taoDon(OrderStatus::Active, [
            'start_time' => now()->subHours(2),
            'end_time' => now()->addMinutes(30),
        ]);
        $this->assertSame(1, $this->quet());

        $don->update(['end_time' => now()->addMinutes(50)]);

        $this->assertSame(1, $this->quet());
        $this->assertSame(2, $this->nhanVien->notifications()->count());
    }

    public function test_qua_han_chi_bao_sau_thoi_gian_an_han(): void
    {
        $grace = (int) config('rental.late_grace_minutes');
        $this->taoDon(OrderStatus::Active, [
            'start_time' => now()->subHours(3),
            'end_time' => now()->subMinute(),
        ]);

        $this->assertSame(0, $this->quet());

        $this->travel($grace + 1)->minutes();

        $this->assertSame(1, $this->quet());
        $this->assertSame(AlertKind::Overdue->value, $this->nhanVien->notifications()->first()->data['kind']);
    }

    public function test_khach_chua_den_nhan_xe_sau_30_phut(): void
    {
        $this->taoDon(OrderStatus::Approved, ['start_time' => now()->subMinutes(31)]);

        $this->assertSame(1, $this->quet());
        $this->assertSame(AlertKind::PickupLate->value, $this->nhanVien->notifications()->first()->data['kind']);
    }

    // ---------- Thông báo tức thì ----------

    public function test_khach_dat_xe_va_tu_huy_deu_bao_nhan_vien(): void
    {
        $khach = User::factory()->create(['role' => 'customer']);
        $xe = Vehicle::factory()->create(['status' => 'available']);
        $booking = app(BookingService::class);

        $don = $booking->create($khach, $xe, now()->addHours(2), now()->addHours(5));
        $booking->cancelByCustomer($don->fresh());

        $this->assertEqualsCanonicalizing(
            [AlertKind::NewBooking->value, AlertKind::CustomerCancelled->value],
            $this->nhanVien->notifications->pluck('data.kind')->all(),
        );
    }

    // ---------- Chuông và trang thông báo ----------

    public function test_khach_khong_xem_duoc_thong_bao(): void
    {
        $khach = User::factory()->create(['role' => 'customer']);

        $this->actingAs($khach)->getJson(route('admin.notifications.feed'))->assertForbidden();
    }

    public function test_feed_tra_so_chua_doc_va_thong_bao_moi_nhat(): void
    {
        $this->bao($this->nhanVien, AlertKind::Overdue, $this->taoDon(OrderStatus::Active));

        $this->actingAs($this->nhanVien)->getJson(route('admin.notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('items.0.tone', AlertKind::Overdue->tone());
    }

    public function test_mo_thong_bao_danh_dau_da_doc_va_chuyen_toi_don(): void
    {
        $don = $this->taoDon(OrderStatus::Active);
        $id = $this->bao($this->nhanVien, AlertKind::Overdue, $don);

        $this->actingAs($this->nhanVien)->get(route('admin.notifications.open', $id))
            ->assertRedirect(route('admin.orders.show', $don));

        $this->assertSame(0, $this->nhanVien->unreadNotifications()->count());
    }

    public function test_khong_mo_duoc_thong_bao_cua_nguoi_khac(): void
    {
        $id = $this->bao($this->admin, AlertKind::Overdue, $this->taoDon(OrderStatus::Active));

        $this->actingAs($this->nhanVien)->get(route('admin.notifications.open', $id))->assertNotFound();

        $this->assertSame(1, $this->admin->unreadNotifications()->count());
    }

    public function test_danh_dau_doc_het_chi_cua_minh(): void
    {
        $don = $this->taoDon(OrderStatus::Active);
        $this->bao($this->nhanVien, AlertKind::Overdue, $don);
        $this->bao($this->nhanVien, AlertKind::ReturnSoon, $don);
        $this->bao($this->admin, AlertKind::Overdue, $don);

        $this->actingAs($this->nhanVien)->post(route('admin.notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $this->nhanVien->unreadNotifications()->count());
        $this->assertSame(1, $this->admin->unreadNotifications()->count());
    }

    public function test_trang_xem_tat_ca_loc_chua_doc(): void
    {
        $don = $this->taoDon(OrderStatus::Active);
        $this->bao($this->nhanVien, AlertKind::ReturnSoon, $don);
        $this->nhanVien->notifications()->first()->markAsRead();
        $this->bao($this->nhanVien, AlertKind::Overdue, $don);

        $this->actingAs($this->nhanVien)->get(route('admin.notifications.index', ['filter' => 'unread']))
            ->assertOk()
            ->assertSee('Quá hạn trả · Đơn #'.$don->id)
            ->assertDontSee('Sắp trả xe · Đơn #'.$don->id);
    }
}
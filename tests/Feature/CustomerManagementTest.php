<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ViolationType;
use App\Models\RentalOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Violation;
use App\Services\ViolationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->freezeTime();
    }

    private function taoNhanVien(string $role = 'staff'): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function taoKhach(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'customer', 'is_active' => true], $attrs));
    }

    private function taoDon(OrderStatus $status, array $attrs = [], ?Vehicle $xe = null): RentalOrder
    {
        $xe ??= Vehicle::factory()->create([
            'status' => $status === OrderStatus::Active ? 'rented' : 'available',
        ]);

        return RentalOrder::create(array_merge([
            'user_id' => $this->taoKhach()->id,
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

    private function ghiViPham(User $khach, ViolationType $loai = ViolationType::Damage): Violation
    {
        return app(ViolationService::class)->record($khach, $loai, ['description' => 'Thử nghiệm']);
    }

    public function test_khach_khong_vao_duoc_trang_khach_hang(): void
    {
        $this->actingAs($this->taoKhach())
            ->get(route('admin.customers.index'))
            ->assertForbidden();
    }

    public function test_nhan_vien_xem_danh_sach_va_tim_theo_sdt(): void
    {
        $this->taoKhach(['name' => 'Nguyễn Tìm Thấy', 'phone' => '0911222333']);
        $this->taoKhach(['name' => 'Trần Không Thấy', 'phone' => '0988777666']);

        $this->actingAs($this->taoNhanVien())
            ->get(route('admin.customers.index', ['q' => '0911']))
            ->assertOk()
            ->assertSee('Nguyễn Tìm Thấy')
            ->assertDontSee('Trần Không Thấy');
    }

    public function test_danh_sach_khong_hien_nhan_vien(): void
    {
        $this->taoNhanVien('staff')->update(['name' => 'Nhân Viên Ẩn']);

        $this->actingAs($this->taoNhanVien('admin'))
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertDontSee('Nhân Viên Ẩn');
    }

    public function test_loc_khach_co_vi_pham(): void
    {
        $this->ghiViPham($this->taoKhach(['name' => 'Khách Có Lỗi']));
        $this->taoKhach(['name' => 'Khách Sạch Sẽ']);

        $this->actingAs($this->taoNhanVien())
            ->get(route('admin.customers.index', ['filter' => 'violating']))
            ->assertOk()
            ->assertSee('Khách Có Lỗi')
            ->assertDontSee('Khách Sạch Sẽ');
    }

    public function test_trang_chi_tiet_hien_vi_pham_va_don(): void
    {
        $don = $this->taoDon(OrderStatus::Completed);
        $this->ghiViPham($don->user);

        $this->actingAs($this->taoNhanVien())
            ->get(route('admin.customers.show', $don->user))
            ->assertOk()
            ->assertSee('Hư hỏng xe')
            ->assertSee('#'.$don->id);
    }

    public function test_mo_chi_tiet_nhan_vien_bao_404(): void
    {
        $admin = $this->taoNhanVien('admin');

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $this->taoNhanVien()))
            ->assertNotFound();
    }

    public function test_admin_khoa_va_mo_khoa_tai_khoan(): void
    {
        $khach = $this->taoKhach(['remember_token' => 'cu']);
        $admin = $this->taoNhanVien('admin');

        $this->actingAs($admin)->post(route('admin.customers.toggle-lock', $khach));
        $khach->refresh();
        $this->assertFalse($khach->is_active);
        $this->assertNotSame('cu', $khach->remember_token);

        $this->actingAs($admin)->post(route('admin.customers.toggle-lock', $khach));
        $this->assertTrue($khach->refresh()->is_active);
    }

    public function test_nhan_vien_khong_khoa_duoc_tai_khoan(): void
    {
        $khach = $this->taoKhach();

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.customers.toggle-lock', $khach))
            ->assertForbidden();

        $this->assertTrue($khach->refresh()->is_active);
    }

    public function test_tra_xe_tre_tu_ghi_vi_pham(): void
    {
        $don = $this->taoDon(OrderStatus::Active, [
            'start_time' => now()->subDay(),
            'end_time' => now()->subHours(2),
        ]);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.orders.complete', $don))
            ->assertSessionHasNoErrors();

        $viPham = Violation::where('rental_order_id', $don->id)->first();
        $this->assertNotNull($viPham);
        $this->assertSame(ViolationType::LateReturn, $viPham->type);
        $this->assertStringContainsString('Trả xe trễ 2 giờ', $viPham->description);
        $this->assertNull($viPham->recorded_by);
        $this->assertSame(1, $don->user->refresh()->violation_count);
    }

    public function test_tra_xe_trong_gio_an_han_khong_ghi_vi_pham(): void
    {
        $don = $this->taoDon(OrderStatus::Active, [
            'start_time' => now()->subDay(),
            'end_time' => now()->subMinutes(config('rental.late_grace_minutes')),
        ]);

        $this->actingAs($this->taoNhanVien())->post(route('admin.orders.complete', $don));

        $this->assertSame(0, Violation::count());
    }

    public function test_ghi_bu_tra_tre_khong_bi_trung(): void
    {
        $don = $this->taoDon(OrderStatus::Completed, [
            'start_time' => now()->subDay(),
            'end_time' => now()->subHours(3),
            'actual_return_time' => now(),
        ]);
        $service = app(ViolationService::class);

        $service->recordLateReturn($don);
        $service->recordLateReturn($don->refresh());

        $this->assertSame(1, Violation::count());
        $this->assertSame(1, $don->user->refresh()->violation_count);
    }

    public function test_nhan_vien_ghi_vi_pham_thu_cong(): void
    {
        $don = $this->taoDon(OrderStatus::Completed);
        $nhanVien = $this->taoNhanVien();

        $this->actingAs($nhanVien)
            ->post(route('admin.customers.violations.store', $don->user), [
                'type' => 'damage',
                'rental_order_id' => $don->id,
                'description' => 'Trầy yếm trái',
                'fine_amount' => 200000,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('violations', [
            'user_id' => $don->user_id,
            'rental_order_id' => $don->id,
            'type' => 'damage',
            'fine_amount' => 200000,
            'recorded_by' => $nhanVien->id,
        ]);
        $this->assertSame(1, $don->user->refresh()->violation_count);
    }

    public function test_khong_ghi_tay_loai_tra_xe_tre(): void
    {
        $khach = $this->taoKhach();

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.customers.violations.store', $khach), [
                'type' => 'late_return',
                'description' => 'Ghi tay',
            ])
            ->assertSessionHasErrors('type');

        $this->assertSame(0, Violation::count());
    }

    public function test_khong_gan_don_cua_khach_khac(): void
    {
        $khach = $this->taoKhach();
        $donNguoiKhac = $this->taoDon(OrderStatus::Completed);

        $this->actingAs($this->taoNhanVien())
            ->post(route('admin.customers.violations.store', $khach), [
                'type' => 'damage',
                'rental_order_id' => $donNguoiKhac->id,
                'description' => 'Sai đơn',
            ])
            ->assertSessionHasErrors('rental_order_id');
    }

    public function test_chi_admin_xoa_duoc_vi_pham(): void
    {
        $khach = $this->taoKhach();
        $viPham = $this->ghiViPham($khach);
        $route = route('admin.customers.violations.destroy', [$khach, $viPham]);

        $this->actingAs($this->taoNhanVien())->delete($route)->assertForbidden();
        $this->assertSame(1, Violation::count());

        $this->actingAs($this->taoNhanVien('admin'))->delete($route)->assertSessionHasNoErrors();
        $this->assertSame(0, Violation::count());
        $this->assertSame(0, $khach->refresh()->violation_count);
    }

    public function test_khong_xoa_vi_pham_qua_url_cua_khach_khac(): void
    {
        $viPham = $this->ghiViPham($this->taoKhach());

        $this->actingAs($this->taoNhanVien('admin'))
            ->delete(route('admin.customers.violations.destroy', [$this->taoKhach(), $viPham]))
            ->assertNotFound();

        $this->assertSame(1, Violation::count());
    }
}
<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Đăng ký ----------

        public function test_trang_dang_ky_da_go_khong_tu_tao_duoc_tai_khoan(): void
    {
        // Sau A5 khách không tự đăng ký; tài khoản khách chỉ được tạo qua đăng nhập bằng mã
        $this->post('/register', [
            'name' => 'Khách',
            'email' => 'khach@test.vn',
            'phone' => '0900000000',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'khach@test.vn']);
    }

    // ---------- Đăng nhập ----------

    public function test_customer_dang_nhap_chuyen_ve_trang_chu(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/');
    }

    public function test_staff_dang_nhap_chuyen_ve_admin(): void
    {
        $user = User::factory()->staff()->create();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/admin');
    }

    public function test_tai_khoan_bi_khoa_khong_dang_nhap_duoc(): void
    {
        $user = User::factory()->staff()->create(['is_active' => false]);

        $this->post('a/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
    }

    // ---------- Lớp 1: middleware auth + role ----------

    public function test_chua_dang_nhap_vao_admin_bi_chuyen_ve_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
    }

    public function test_customer_vao_admin_bi_403(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    // ---------- Lớp 2 + 3: Gate và @can ----------

        public function test_staff_vao_dashboard_thay_ca_doanh_thu(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Đơn chờ duyệt')
            ->assertSee('Doanh thu hôm nay');
    }

    public function test_admin_thay_doanh_thu(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Doanh thu hôm nay');
    }

    public function test_admin_ke_thua_moi_quyen_cua_staff(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['dashboard.view', 'gps.monitor', 'order.manage', 'customer.view'] as $ability) {
            $this->assertTrue($admin->can($ability), "Admin phải có quyền {$ability}");
        }
    }

        public function test_staff_co_moi_quyen_quan_ly_tru_quan_ly_nhan_vien(): void
    {
        $staff = User::factory()->staff()->create();
        $khach = User::factory()->create(['role' => 'customer']);

        foreach (['vehicle.manage', 'vehicle.delete', 'store.manage', 'pricing.manage', 'report.view', 'customer.lock', 'violation.delete'] as $ability) {
            $this->assertTrue($staff->can($ability), "Staff phải có quyền {$ability}");
            $this->assertFalse($khach->can($ability), "Khách không được có quyền {$ability}");
        }

        $this->assertFalse($staff->can('user.manage'), 'Chỉ admin được quản lý tài khoản nhân viên');
    }
}
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StaffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function nhanVien(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'staff', 'is_active' => true], $attrs));
    }

    private function duLieu(array $doi = []): array
    {
        return array_merge([
            'name' => 'Nguyễn Văn Nhân',
            'email' => 'nhanvien@test.vn',
            'phone' => '0911222333',
            'role' => 'staff',
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
        ], $doi);
    }

    public function test_staff_khong_vao_duoc_trang_nhan_vien(): void
    {
        $staff = $this->nhanVien();

        $this->actingAs($staff)->get(route('admin.staff.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.staff.store'), $this->duLieu())->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'nhanvien@test.vn']);
    }

    public function test_admin_xem_danh_sach_khong_hien_khach_va_tim_theo_email(): void
    {
        $this->nhanVien(['name' => 'Trần Thu Ngân', 'email' => 'ngan@test.vn']);
        $this->nhanVien(['name' => 'Lê Bảo Vệ', 'email' => 'baove@test.vn']);
        User::factory()->create(['role' => 'customer', 'name' => 'Khách Lẻ']);

        $this->actingAs($this->admin)->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Trần Thu Ngân')
            ->assertSee('Lê Bảo Vệ')
            ->assertDontSee('Khách Lẻ');

        $this->actingAs($this->admin)->get(route('admin.staff.index', ['q' => 'ngan@']))
            ->assertSee('Trần Thu Ngân')
            ->assertDontSee('Lê Bảo Vệ');
    }

    public function test_admin_them_nhan_vien_va_nhan_vien_dang_nhap_duoc(): void
    {
        $this->actingAs($this->admin)->post(route('admin.staff.store'), $this->duLieu())
            ->assertRedirect(route('admin.staff.index'));

        $moi = User::where('email', 'nhanvien@test.vn')->first();
        $this->assertSame('staff', StaffService::roleOf($moi));
        $this->assertTrue((bool) $moi->is_active);

        Auth::logout();
        $this->post('/admin/login', ['email' => 'nhanvien@test.vn', 'password' => 'matkhau123']);
        $this->assertAuthenticatedAs($moi);
    }

    public function test_email_trung_va_mat_khau_nhap_lai_sai_bi_bao_loi(): void
    {
        $this->nhanVien(['email' => 'nhanvien@test.vn']);

        $this->actingAs($this->admin)
            ->post(route('admin.staff.store'), $this->duLieu(['password_confirmation' => 'khac12345']))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertSame(1, User::where('email', 'nhanvien@test.vn')->count());
    }

    public function test_sua_de_trong_mat_khau_thi_giu_mat_khau_cu(): void
    {
        $staff = $this->nhanVien();
        $matKhauCu = $staff->password;

        $this->actingAs($this->admin)->put(route('admin.staff.update', $staff), $this->duLieu([
            'name' => 'Tên Mới',
            'email' => $staff->email,
            'password' => '',
            'password_confirmation' => '',
        ]))->assertSessionHasNoErrors();

        $staff->refresh();
        $this->assertSame('Tên Mới', $staff->name);
        $this->assertSame($matKhauCu, $staff->password);
    }

    public function test_admin_doi_vai_tro_va_mat_khau_nhan_vien(): void
    {
        $staff = $this->nhanVien();

        $this->actingAs($this->admin)->put(route('admin.staff.update', $staff), $this->duLieu([
            'email' => $staff->email,
            'role' => 'admin',
            'password' => 'matkhaumoi9',
            'password_confirmation' => 'matkhaumoi9',
        ]))->assertSessionHasNoErrors();

        $staff->refresh();
        $this->assertSame('admin', StaffService::roleOf($staff));
        $this->assertTrue(Hash::check('matkhaumoi9', $staff->password));
    }

    public function test_khoa_thi_khong_dang_nhap_duoc_mo_khoa_thi_duoc(): void
    {
        $staff = $this->nhanVien();

        $this->actingAs($this->admin)->post(route('admin.staff.toggle-lock', $staff))->assertSessionHasNoErrors();
        $this->assertFalse((bool) $staff->refresh()->is_active);

        Auth::logout();
        $this->post('/admin/login', ['email' => $staff->email, 'password' => 'password']);
        $this->assertGuest();

        $this->actingAs($this->admin)->post(route('admin.staff.toggle-lock', $staff));
        $this->assertTrue((bool) $staff->refresh()->is_active);
    }

    public function test_admin_khong_tu_khoa_hay_tu_doi_vai_tro_cua_minh(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.staff.toggle-lock', $this->admin))
            ->assertSessionHasErrors('staff');

        $this->actingAs($this->admin)->put(route('admin.staff.update', $this->admin), $this->duLieu([
            'email' => $this->admin->email,
            'role' => 'staff',
            'password' => '',
            'password_confirmation' => '',
        ]))->assertSessionHasErrors('role');

        $this->admin->refresh();
        $this->assertTrue((bool) $this->admin->is_active);
        $this->assertSame('admin', StaffService::roleOf($this->admin));
    }

    public function test_mo_tai_khoan_khach_qua_trang_nhan_vien_bao_404(): void
    {
        $khach = User::factory()->create(['role' => 'customer']);

        $this->actingAs($this->admin)->get(route('admin.staff.edit', $khach))->assertNotFound();
        $this->actingAs($this->admin)->post(route('admin.staff.toggle-lock', $khach))->assertNotFound();
    }

    public function test_chi_admin_thay_muc_nhan_vien_tren_sidebar(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertSee(route('admin.staff.index'));
        $this->actingAs($this->nhanVien())->get(route('admin.dashboard'))->assertDontSee(route('admin.staff.index'));
    }
}
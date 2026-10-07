<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    private function validData(array $override = []): array
    {
        return array_merge([
            'name' => 'Cửa hàng Quận 3',
            'address' => 'Võ Văn Tần, Quận 3',
            'phone' => '0281234567',
            'latitude' => 10.7756,
            'longitude' => 106.6880,
        ], $override);
    }

    private function makeStore(): Store
    {
        return Store::create($this->validData());
    }

    public function test_staff_khong_vao_duoc_quan_ly_cua_hang(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/admin/stores')
            ->assertForbidden();
    }

    public function test_admin_xem_va_tim_kiem_danh_sach(): void
    {
        $this->makeStore();
        Store::create($this->validData(['name' => 'Cửa hàng Thủ Đức', 'address' => 'Võ Văn Ngân']));

        $this->actingAs($this->admin)
            ->get('/admin/stores?q=Thủ Đức')
            ->assertOk()
            ->assertSee('Cửa hàng Thủ Đức')
            ->assertDontSee('Cửa hàng Quận 3');
    }

    public function test_admin_them_cua_hang_hop_le(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/stores', $this->validData())
            ->assertRedirect('/admin/stores')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('stores', ['name' => 'Cửa hàng Quận 3']);
    }

    public function test_them_cua_hang_thieu_toa_do_bi_bao_loi(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/stores', $this->validData(['latitude' => null, 'longitude' => null]))
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->assertDatabaseCount('stores', 0);
    }

    public function test_toa_do_ngoai_khoang_bi_bao_loi(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/stores', $this->validData(['latitude' => 200]))
            ->assertSessionHasErrors('latitude');
    }

    public function test_admin_sua_cua_hang(): void
    {
        $store = $this->makeStore();

        $this->actingAs($this->admin)
            ->put("/admin/stores/{$store->id}", $this->validData(['name' => 'Tên mới']))
            ->assertRedirect('/admin/stores');

        $this->assertSame('Tên mới', $store->fresh()->name);
    }

    public function test_khong_xoa_duoc_cua_hang_con_xe(): void
    {
        $store = $this->makeStore();
        Vehicle::create([
            'store_id' => $store->id, 'license_plate' => '59X1-000.01', 'name' => 'Xe',
            'price_per_hour' => 1, 'price_per_day' => 1, 'price_per_week' => 1,
        ]);

        $this->actingAs($this->admin)
            ->delete("/admin/stores/{$store->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('stores', ['id' => $store->id]);
    }

    public function test_khong_xoa_duoc_cua_hang_con_xe_da_xoa_mem(): void
    {
        $store = $this->makeStore();
        $vehicle = Vehicle::create([
            'store_id' => $store->id, 'license_plate' => '59X1-000.02', 'name' => 'Xe',
            'price_per_hour' => 1, 'price_per_day' => 1, 'price_per_week' => 1,
        ]);
        $vehicle->delete();

        $this->actingAs($this->admin)
            ->delete("/admin/stores/{$store->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('stores', ['id' => $store->id]);
    }

    public function test_xoa_duoc_cua_hang_khong_co_xe(): void
    {
        $store = $this->makeStore();

        $this->actingAs($this->admin)
            ->delete("/admin/stores/{$store->id}")
            ->assertRedirect('/admin/stores');

        $this->assertDatabaseMissing('stores', ['id' => $store->id]);
    }
}
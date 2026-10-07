<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
        $this->store = Store::create(['name' => 'CH test', 'address' => 'HCM', 'latitude' => 10.77, 'longitude' => 106.70]);
    }

    private function vehicle(string $plate, string $type, int $day = 150000): Vehicle
    {
        return Vehicle::create([
            'store_id' => $this->store->id, 'license_plate' => $plate, 'name' => 'Xe', 'type' => $type,
            'price_per_hour' => 30000, 'price_per_day' => $day, 'price_per_week' => 900000,
        ]);
    }

    public function test_staff_khong_vao_duoc_trang_gia(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/admin/pricing')
            ->assertForbidden();
    }

    public function test_ap_gia_theo_loai_chi_doi_xe_cung_loai_va_bo_qua_xe_da_xoa(): void
    {
        $a = $this->vehicle('59X1-000.01', 'Tay ga');
        $b = $this->vehicle('59X1-000.02', 'Xe số');
        $c = $this->vehicle('59X1-000.03', 'Tay ga');
        $c->delete();

        $this->actingAs($this->admin)
            ->put('/admin/pricing/by-type', [
                'type' => 'Tay ga', 'price_per_hour' => 40000, 'price_per_day' => 200000, 'price_per_week' => 1200000,
            ])
            ->assertSessionHas('success');

        $this->assertEquals(200000, $a->fresh()->price_per_day);
        $this->assertEquals(150000, $b->fresh()->price_per_day);
        $this->assertEquals(150000, Vehicle::withTrashed()->find($c->id)->price_per_day);
    }

    public function test_ap_gia_theo_loai_du_lieu_sai_bi_bao_loi(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/pricing/by-type', [
                'type' => 'Máy bay', 'price_per_hour' => 40000, 'price_per_day' => 500, 'price_per_week' => 1200000,
            ])
            ->assertSessionHasErrors(['type', 'price_per_day']);
    }

    public function test_sua_gia_tung_xe_chi_luu_xe_thay_doi(): void
    {
        $a = $this->vehicle('59X1-000.01', 'Tay ga');
        $b = $this->vehicle('59X1-000.02', 'Xe số');

        $this->actingAs($this->admin)
            ->put('/admin/pricing/vehicles', ['prices' => [
                $a->id => ['price_per_hour' => 30000, 'price_per_day' => 180000, 'price_per_week' => 900000],
                $b->id => ['price_per_hour' => 30000, 'price_per_day' => 150000, 'price_per_week' => 900000],
            ]])
            ->assertSessionHas('success', 'Đã cập nhật giá cho 1 xe.');

        $this->assertEquals(180000, $a->fresh()->price_per_day);
    }

    public function test_gia_tung_xe_khong_hop_le_bao_loi_dung_o(): void
    {
        $a = $this->vehicle('59X1-000.01', 'Tay ga');

        $this->actingAs($this->admin)
            ->put('/admin/pricing/vehicles', ['prices' => [
                $a->id => ['price_per_hour' => 30000, 'price_per_day' => 0, 'price_per_week' => 900000],
            ]])
            ->assertSessionHasErrors("prices.{$a->id}.price_per_day");
    }
}
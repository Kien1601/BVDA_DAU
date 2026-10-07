<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $store = Store::create(['name' => 'CH test', 'address' => 'HCM', 'latitude' => 10.77, 'longitude' => 106.70]);

        $base = ['store_id' => $store->id, 'price_per_hour' => 1, 'price_per_day' => 1, 'price_per_week' => 1];

        Vehicle::create($base + ['license_plate' => '59X1-000.01', 'name' => 'Có GPS', 'gps_device_id' => '860000000000001']);
        Vehicle::create($base + ['license_plate' => '59X1-000.02', 'name' => 'Không GPS']);
    }

    public function test_chua_dang_nhap_bi_chuyen_ve_login(): void
    {
        $this->get('/admin/gps-monitor/positions')->assertRedirect('/login');
    }

    public function test_customer_khong_lay_duoc_vi_tri(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/admin/gps-monitor/positions')
            ->assertForbidden();
    }

    public function test_staff_chi_nhan_xe_co_gan_imei(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->getJson('/admin/gps-monitor/positions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.license_plate', '59X1-000.01');
    }

    public function test_chi_staff_va_admin_duoc_nghe_kenh_vi_tri(): void
    {
        $this->assertFalse(User::factory()->create()->can('gps.monitor'));
        $this->assertTrue(User::factory()->staff()->create()->can('gps.monitor'));
        $this->assertTrue(User::factory()->admin()->create()->can('gps.monitor'));
    }
}
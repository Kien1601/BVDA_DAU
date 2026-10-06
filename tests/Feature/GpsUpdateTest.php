<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GpsUpdateTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-token';
    private const IMEI = '860000000000001';

    protected function setUp(): void
    {
        parent::setUp();

        config(['gps.api_token' => self::TOKEN, 'gps.speed_unit' => 'knots']);

        $store = Store::create(['name' => 'CH test', 'address' => 'HCM', 'latitude' => 10.77, 'longitude' => 106.70]);

        Vehicle::create([
            'store_id' => $store->id, 'license_plate' => '59X1-000.01', 'name' => 'Xe test',
            'price_per_hour' => 1, 'price_per_day' => 1, 'price_per_week' => 1,
            'gps_device_id' => self::IMEI,
        ]);
    }

    private function payload(array $override = []): array
    {
        return array_replace_recursive([
            'device' => ['uniqueId' => self::IMEI],
            'position' => [
                'latitude' => 10.7769, 'longitude' => 106.7009,
                'speed' => 10, 'deviceTime' => now()->toIso8601String(),
            ],
        ], $override);
    }

    private function send(array $payload, ?string $token = self::TOKEN)
    {
        return $this->postJson('/api/gps/update', $payload, $token ? ['Authorization' => "Bearer {$token}"] : []);
    }

    public function test_sai_token_bi_401(): void
    {
        $this->send($this->payload(), 'sai-token')->assertUnauthorized();
        $this->assertDatabaseCount('gps_locations', 0);
    }

    public function test_thieu_token_bi_401(): void
    {
        $this->send($this->payload(), null)->assertUnauthorized();
    }

    public function test_du_lieu_sai_bi_422(): void
    {
        $this->send($this->payload(['position' => ['latitude' => 999]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('position.latitude');
    }

    public function test_imei_la_bi_404_va_khong_luu(): void
    {
        $this->send($this->payload(['device' => ['uniqueId' => '999']]))->assertNotFound();
        $this->assertDatabaseCount('gps_locations', 0);
    }

    public function test_hop_le_thi_luu_lich_su_va_cap_nhat_vi_tri_cuoi(): void
    {
        $this->send($this->payload())->assertCreated();

        $this->assertDatabaseCount('gps_locations', 1);
        $this->assertEqualsWithDelta(18.52, \App\Models\GpsLocation::first()->speed, 0.01); // knots -> km/h

        $vehicle = Vehicle::first();
        $this->assertEqualsWithDelta(10.7769, $vehicle->last_lat, 0.00001);
        $this->assertNotNull($vehicle->last_signal_at);
    }

    public function test_goi_tin_den_muon_van_luu_nhung_khong_ghi_de_vi_tri_cuoi(): void
    {
        $this->send($this->payload())->assertCreated();

        $this->send($this->payload([
            'position' => ['latitude' => 10.8000, 'deviceTime' => now()->subHour()->toIso8601String()],
        ]))->assertCreated();

        $this->assertDatabaseCount('gps_locations', 2);
        $this->assertEqualsWithDelta(10.7769, Vehicle::first()->last_lat, 0.00001);
    }

    public function test_xe_da_xoa_mem_khong_nhan_gps(): void
    {
        Vehicle::first()->delete();

        $this->send($this->payload())->assertNotFound();
    }
}
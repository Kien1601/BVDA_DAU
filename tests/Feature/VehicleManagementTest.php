<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\Store;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');

        $this->admin = User::factory()->admin()->create();
        $this->store = Store::create(['name' => 'CH test', 'address' => 'HCM', 'latitude' => 10.77, 'longitude' => 106.70]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'store_id' => $this->store->id,
            'license_plate' => '59X1-999.99',
            'name' => 'Honda Vision',
            'brand' => 'Honda',
            'type' => 'Tay ga',
            'price_per_hour' => 30000,
            'price_per_day' => 150000,
            'price_per_week' => 900000,
            'gps_device_id' => '',
            'status' => 'available',
        ], $override);
    }

    private function makeVehicle(array $override = []): Vehicle
    {
        $data = $this->payload($override);
        $data['gps_device_id'] = $data['gps_device_id'] ?: null;

        return Vehicle::create($data);
    }

    public function test_staff_khong_vao_duoc_quan_ly_xe(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/admin/vehicles')
            ->assertForbidden();
    }

    public function test_admin_them_xe_kem_anh(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/vehicles', $this->payload([
                'image' => UploadedFile::fake()->image('xe.jpg', 800, 600),
            ]))
            ->assertRedirect('/admin/vehicles');

        $vehicle = Vehicle::first();
        $this->assertSame(VehicleStatus::Available, $vehicle->status);
        Storage::disk('public')->assertExists($vehicle->image);
    }

    public function test_bien_so_duoc_chuan_hoa_va_khong_duoc_trung(): void
    {
        $this->makeVehicle();

        $this->actingAs($this->admin)
            ->post('/admin/vehicles', $this->payload(['license_plate' => ' 59x1-999.99 ']))
            ->assertSessionHasErrors('license_plate');
    }

    public function test_imei_khong_duoc_trung_va_chi_gom_chu_so(): void
    {
        $this->makeVehicle(['gps_device_id' => '860000000000001']);

        $this->actingAs($this->admin)
            ->post('/admin/vehicles', $this->payload(['license_plate' => '59X1-111.22', 'gps_device_id' => '860000000000001']))
            ->assertSessionHasErrors('gps_device_id');

        $this->actingAs($this->admin)
            ->post('/admin/vehicles', $this->payload(['license_plate' => '59X1-111.33', 'gps_device_id' => '86000ABC00001']))
            ->assertSessionHasErrors('gps_device_id');
    }

    public function test_xoa_trong_imei_la_go_thiet_bi(): void
    {
        $vehicle = $this->makeVehicle(['gps_device_id' => '860000000000001']);

        $this->actingAs($this->admin)
            ->put("/admin/vehicles/{$vehicle->id}", $this->payload(['gps_device_id' => '']))
            ->assertRedirect('/admin/vehicles');

        $this->assertNull($vehicle->fresh()->gps_device_id);
    }

    public function test_khong_duoc_chon_tay_trang_thai_dang_cho_thue(): void
    {
        $vehicle = $this->makeVehicle();

        $this->actingAs($this->admin)
            ->put("/admin/vehicles/{$vehicle->id}", $this->payload(['status' => 'rented']))
            ->assertSessionHasErrors('status');

        $this->assertSame(VehicleStatus::Available, $vehicle->fresh()->status);
    }

    public function test_xe_dang_cho_thue_khong_doi_duoc_trang_thai(): void
    {
        $vehicle = $this->makeVehicle();
        $vehicle->forceFill(['status' => VehicleStatus::Rented])->save();

        $this->actingAs($this->admin)
            ->put("/admin/vehicles/{$vehicle->id}", $this->payload(['status' => 'maintenance']))
            ->assertSessionHasErrors('status');

        $this->assertSame(VehicleStatus::Rented, $vehicle->fresh()->status);
    }

    public function test_doi_anh_thi_xoa_anh_cu(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/vehicles', $this->payload(['image' => UploadedFile::fake()->image('cu.jpg')]));
        $vehicle = Vehicle::first();
        $old = $vehicle->image;

        $this->actingAs($this->admin)
            ->put("/admin/vehicles/{$vehicle->id}", $this->payload(['image' => UploadedFile::fake()->image('moi.jpg')]));

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($vehicle->fresh()->image);
    }

    public function test_khong_xoa_duoc_xe_dang_cho_thue(): void
    {
        $vehicle = $this->makeVehicle();
        $vehicle->forceFill(['status' => VehicleStatus::Rented])->save();

        $this->actingAs($this->admin)
            ->delete("/admin/vehicles/{$vehicle->id}")
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($vehicle);
    }

    public function test_xoa_mem_va_go_imei(): void
    {
        $vehicle = $this->makeVehicle(['gps_device_id' => '860000000000001']);

        $this->actingAs($this->admin)
            ->delete("/admin/vehicles/{$vehicle->id}")
            ->assertRedirect('/admin/vehicles');

        $this->assertSoftDeleted($vehicle);
        $this->assertNull(Vehicle::withTrashed()->find($vehicle->id)->gps_device_id);
    }

    public function test_loc_theo_trang_thai(): void
    {
        $this->makeVehicle(['license_plate' => '59X1-AAA.01', 'status' => 'available']);
        $this->makeVehicle(['license_plate' => '59X1-BBB.02', 'status' => 'maintenance']);

        $this->actingAs($this->admin)
            ->get('/admin/vehicles?status=maintenance')
            ->assertOk()
            ->assertSee('59X1-BBB.02')
            ->assertDontSee('59X1-AAA.01');
    }
}
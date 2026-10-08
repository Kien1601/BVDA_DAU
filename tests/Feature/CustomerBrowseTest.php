<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\Store;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBrowseTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->store = Store::create(['name' => 'Cửa hàng Quận 1', 'address' => 'Nguyễn Huệ', 'latitude' => 10.7769, 'longitude' => 106.7009]);
    }

    private function vehicle(array $override = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'store_id' => $this->store->id, 'license_plate' => '59X1-' . random_int(100, 999) . '.' . random_int(10, 99),
            'name' => 'Honda Vision', 'brand' => 'Honda', 'type' => 'Tay ga',
            'price_per_hour' => 30000, 'price_per_day' => 150000, 'price_per_week' => 900000,
        ], $override));
    }

    public function test_trang_chu_co_canh_3d_chat_luong_cao_va_xe_noi_bat(): void
    {
        $this->vehicle(['name' => 'Yamaha Grande']);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-quality="high"', false)
            ->assertSee('Yamaha Grande');
    }

    public function test_khach_khong_thay_xe_bao_duong_va_xe_da_xoa(): void
    {
        $this->vehicle(['name' => 'Xe Hien Thi']);
        $this->vehicle(['name' => 'Xe Bao Duong', 'status' => VehicleStatus::Maintenance]);
        $this->vehicle(['name' => 'Xe Da Xoa'])->delete();

        $this->get('/vehicles')
            ->assertOk()
            ->assertSee('Xe Hien Thi')
            ->assertDontSee('Xe Bao Duong')
            ->assertDontSee('Xe Da Xoa');
    }

    public function test_xe_dang_cho_thue_van_hien_kem_nhan(): void
    {
        $this->vehicle(['name' => 'Xe Dang Thue', 'status' => VehicleStatus::Rented]);

        $this->get('/vehicles')->assertSee('Xe Dang Thue')->assertSee('Đang có khách');
    }

    public function test_loc_theo_loai_va_gia(): void
    {
        $this->vehicle(['name' => 'Tay Ga Re', 'type' => 'Tay ga', 'price_per_day' => 90000]);
        $this->vehicle(['name' => 'Tay Ga Dat', 'type' => 'Tay ga', 'price_per_day' => 250000]);
        $this->vehicle(['name' => 'Xe So Re', 'type' => 'Xe số', 'price_per_day' => 80000]);

        $this->get('/vehicles?type=Tay+ga&max_price=100000')
            ->assertSee('Tay Ga Re')
            ->assertDontSee('Tay Ga Dat')
            ->assertDontSee('Xe So Re');
    }

    public function test_sap_xep_theo_gia_tang_dan(): void
    {
        $this->vehicle(['name' => 'Xe Gia Cao', 'price_per_day' => 300000]);
        $this->vehicle(['name' => 'Xe Gia Thap', 'price_per_day' => 90000]);

        $this->get('/vehicles?sort=price_asc')->assertSeeInOrder(['Xe Gia Thap', 'Xe Gia Cao']);
    }

    public function test_gia_tri_loc_la_bi_bo_qua(): void
    {
        $this->vehicle(['name' => 'Xe Bat Ky']);

        $this->get('/vehicles?type=MayBay&sort=hack')->assertOk()->assertSee('Xe Bat Ky');
    }

    public function test_chi_tiet_xe_bao_duong_hoac_da_xoa_tra_404(): void
    {
        $maintenance = $this->vehicle(['status' => VehicleStatus::Maintenance]);
        $deleted = $this->vehicle();
        $deleted->delete();

        $this->get("/vehicles/{$maintenance->id}")->assertNotFound();
        $this->get("/vehicles/{$deleted->id}")->assertNotFound();
    }

    public function test_trang_khach_khong_lo_imei_va_vi_tri_gps(): void
    {
        $vehicle = $this->vehicle(['gps_device_id' => '860000000000777']);
        $vehicle->forceFill(['last_lat' => 10.1234567, 'last_lng' => 106.7654321])->save();

        foreach (['/', '/vehicles', "/vehicles/{$vehicle->id}", '/stores'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('860000000000777')
                ->assertDontSee('10.1234567')
                ->assertDontSee('106.7654321');
        }
    }

    public function test_tim_kiem_khong_tim_theo_imei(): void
    {
        $this->vehicle(['name' => 'Xe Co Thiet Bi', 'gps_device_id' => '860000000000888']);

        $this->get('/vehicles?q=860000000000888')->assertDontSee('Xe Co Thiet Bi');
    }

    public function test_trang_cua_hang_co_ban_do_va_so_xe(): void
    {
        $this->vehicle();

        $this->get('/stores')
            ->assertOk()
            ->assertSee('Cửa hàng Quận 1')
            ->assertSee('id="store-map"', false)
            ->assertSee('1 xe sẵn sàng');
    }

    public function test_khach_chua_dang_nhap_duoc_moi_dang_nhap_de_dat_thue(): void
    {
        $vehicle = $this->vehicle();

        $this->get("/vehicles/{$vehicle->id}")->assertSee('Đăng nhập để đặt thuê');
    }

    public function test_nhan_vien_thay_nut_khu_quan_ly(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/')
            ->assertSee('Khu quản lý');
    }
}
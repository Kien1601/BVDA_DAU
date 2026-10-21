<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\VehicleStatus;
use App\Models\RentalOrder;
use App\Models\Store;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FleetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetTest extends TestCase
{
    use RefreshDatabase;

    private function fleet(): FleetService
    {
        return app(FleetService::class);
    }

    private function xe(array $attrs = []): Vehicle
    {
        return Vehicle::factory()->create($attrs);
    }

    private function don(Vehicle $xe, OrderStatus $status): RentalOrder
    {
        return RentalOrder::create([
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
        ]);
    }

    public function test_tong_quan_dem_dung_tung_trang_thai(): void
    {
        $this->xe();
        $this->xe();
        $this->xe(['status' => VehicleStatus::Rented]);
        $this->xe(['status' => VehicleStatus::Maintenance]);
        $this->don($this->xe(), OrderStatus::Approved);

        $this->assertSame(
            ['total' => 5, 'available' => 2, 'booked' => 1, 'rented' => 1, 'maintenance' => 1],
            $this->fleet()->summary()
        );
    }

    public function test_chi_don_cho_duyet_va_da_duyet_moi_tinh_da_dat(): void
    {
        $this->don($this->xe(), OrderStatus::Pending);
        $this->don($this->xe(), OrderStatus::Cancelled);
        $this->don($this->xe(), OrderStatus::Completed);

        $summary = $this->fleet()->summary();

        $this->assertSame(1, $summary['booked']);
        $this->assertSame(2, $summary['available']);
    }

    public function test_mot_xe_nhieu_don_chi_tinh_mot_lan(): void
    {
        $xe = $this->xe();
        $this->don($xe, OrderStatus::Pending);
        $this->don($xe, OrderStatus::Approved);

        $this->assertSame(1, $this->fleet()->summary()['booked']);
    }

    public function test_xe_da_xoa_mem_khong_duoc_dem(): void
    {
        $this->xe();
        $this->xe()->delete();

        $this->assertSame(1, $this->fleet()->summary()['total']);
    }

    public function test_loc_theo_cua_hang(): void
    {
        $a = Store::factory()->create();
        $b = Store::factory()->create();
        $this->xe(['store_id' => $a->id]);
        $this->xe(['store_id' => $a->id]);
        $this->xe(['store_id' => $b->id]);

        $this->assertSame(2, $this->fleet()->summary($a->id)['total']);
        $this->assertSame(1, $this->fleet()->summary($b->id)['total']);
    }

    public function test_bang_theo_loai_bao_het_sap_het_con_xe(): void
    {
        $this->don($this->xe(['type' => 'Xe số']), OrderStatus::Approved);
        $this->xe(['type' => 'Tay ga']);
        $this->xe(['type' => 'Tay ga']);
        foreach (range(1, 3) as $i) {
            $this->xe(['type' => 'Côn tay']);
        }

        $rows = $this->fleet()->byType()->keyBy('type');

        $this->assertSame('out', $rows['Xe số']['stock']);
        $this->assertSame(100, $rows['Xe số']['usage']);
        $this->assertSame('low', $rows['Tay ga']['stock']);
        $this->assertSame('ok', $rows['Côn tay']['stock']);
    }

    public function test_admin_va_nhan_vien_xem_duoc_trang_doi_xe(): void
    {
        $this->xe();

        foreach (['admin', 'staff'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get('/admin/fleet')
                ->assertOk()
                ->assertSee('Tình trạng đội xe');
        }
    }

    public function test_khach_khong_vao_duoc_trang_doi_xe(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get('/admin/fleet')
            ->assertForbidden();
    }

    public function test_trang_khach_hien_so_xe_con_theo_loai(): void
    {
        $this->xe(['type' => 'Tay ga']);
        $this->xe(['type' => 'Tay ga']);
        $this->don($this->xe(['type' => 'Xe số']), OrderStatus::Approved);

        $this->get('/vehicles')
            ->assertOk()
            ->assertSee('Còn 2 xe')
            ->assertSee('Hết xe');
    }
}
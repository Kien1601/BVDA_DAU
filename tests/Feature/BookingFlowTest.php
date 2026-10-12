<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\RentalOrder;
use App\Models\Store;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Carbon::setTestNow('2026-10-20 08:00:00');

        $this->customer = User::factory()->create();
        $store = Store::create(['name' => 'CH test', 'address' => 'Đà Nẵng', 'latitude' => 16.06, 'longitude' => 108.22]);
        $this->vehicle = Vehicle::create([
            'store_id' => $store->id, 'license_plate' => '43B1-000.01', 'name' => 'Xe test', 'type' => 'Tay ga',
            'price_per_hour' => 30000, 'price_per_day' => 150000, 'price_per_week' => 900000,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function form(array $override = []): array
    {
        return array_merge(['start_time' => '2026-10-21T09:00', 'end_time' => '2026-10-21T12:00'], $override);
    }

    public function test_chua_dang_nhap_bi_chuyen_ve_trang_dang_nhap(): void
    {
        $this->get("/vehicles/{$this->vehicle->id}/book")->assertRedirect('/login');
    }

    public function test_nhan_vien_khong_dat_thue_duoc(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get("/vehicles/{$this->vehicle->id}/book")
            ->assertForbidden();
    }

    public function test_khach_mo_duoc_trang_dat_thue(): void
    {
        $this->actingAs($this->customer)
            ->get("/vehicles/{$this->vehicle->id}/book")
            ->assertOk()
            ->assertSee('Xác nhận đặt thuê')
            ->assertSee('data-quote-url', false);
    }

    public function test_bao_gia_tra_ve_json(): void
    {
        $this->actingAs($this->customer)
            ->postJson("/vehicles/{$this->vehicle->id}/quote", $this->form())
            ->assertOk()
            ->assertJson(['breakdown' => '3 giờ', 'subtotal' => 90000, 'total' => 90000, 'deposit' => 100000]);
    }

    public function test_bao_gia_trung_lich_tra_422(): void
    {
        RentalOrder::create([
            'user_id' => User::factory()->create()->id, 'vehicle_id' => $this->vehicle->id,
            'start_time' => '2026-10-21 10:00', 'end_time' => '2026-10-21 11:00', 'status' => OrderStatus::Approved,
            'subtotal_amount' => 30000, 'total_amount' => 30000, 'deposit_amount' => 100000,
        ]);

        $this->actingAs($this->customer)
            ->postJson("/vehicles/{$this->vehicle->id}/quote", $this->form())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_time');
    }

    public function test_dat_thue_tao_don_va_chuyen_toi_trang_don(): void
    {
        $response = $this->actingAs($this->customer)
            ->post("/vehicles/{$this->vehicle->id}/book", $this->form(['note' => 'Cần mũ trẻ em']));

        $order = RentalOrder::first();
        $response->assertRedirect("/booking/{$order->id}");
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame('Cần mũ trẻ em', $order->note);
    }

    public function test_ma_khuyen_mai_sai_khong_tao_don(): void
    {
        $this->actingAs($this->customer)
            ->post("/vehicles/{$this->vehicle->id}/book", $this->form(['promotion_code' => 'KHONGCO']))
            ->assertSessionHasErrors('promotion_code');

        $this->assertDatabaseCount('rental_orders', 0);
    }

    public function test_chu_don_xem_duoc_nguoi_khac_bi_403(): void
    {
        $this->actingAs($this->customer)->post("/vehicles/{$this->vehicle->id}/book", $this->form());
        $order = RentalOrder::first();

        $this->actingAs($this->customer)->get("/booking/{$order->id}")
            ->assertOk()
            ->assertSee('Chờ duyệt')
            ->assertSee('data-countdown', false);

        $this->actingAs(User::factory()->create())->get("/booking/{$order->id}")->assertForbidden();
    }

    public function test_chu_don_huy_duoc_nguoi_khac_khong_huy_duoc(): void
    {
        $this->actingAs($this->customer)->post("/vehicles/{$this->vehicle->id}/book", $this->form());
        $order = RentalOrder::first();

        $this->actingAs(User::factory()->create())->post("/booking/{$order->id}/cancel")->assertForbidden();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);

        $this->actingAs($this->customer)->post("/booking/{$order->id}/cancel")->assertRedirect("/booking/{$order->id}");
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_trang_chi_tiet_xe_co_nut_dat_thue_cho_khach(): void
    {
        $this->actingAs($this->customer)
            ->get("/vehicles/{$this->vehicle->id}")
            ->assertSee('Đặt thuê xe')
            ->assertSee("/vehicles/{$this->vehicle->id}/book", false);
    }
}
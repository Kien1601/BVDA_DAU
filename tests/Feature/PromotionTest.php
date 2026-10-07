<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    private function data(array $override = []): array
    {
        return array_merge([
            'code' => 'khaitruong', 'discount_type' => 'percent', 'discount_value' => 10,
            'start_date' => today()->toDateString(), 'end_date' => today()->addDays(30)->toDateString(),
            'is_active' => '1',
        ], $override);
    }

    private function promo(array $override = []): Promotion
    {
        return Promotion::create(array_merge([
            'code' => 'MAU', 'discount_percent' => 10, 'discount_amount' => null,
            'start_date' => today(), 'end_date' => today()->addDays(5), 'is_active' => true,
        ], $override));
    }

    public function test_tao_ma_giam_phan_tram_luu_chu_hoa(): void
    {
        $this->actingAs($this->admin)->post('/admin/promotions', $this->data())
            ->assertRedirect('/admin/promotions');

        $p = Promotion::first();
        $this->assertSame('KHAITRUONG', $p->code);
        $this->assertSame(10, $p->discount_percent);
        $this->assertNull($p->discount_amount);
    }

    public function test_tao_ma_giam_so_tien(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/promotions', $this->data(['discount_type' => 'amount', 'discount_value' => 50000]));

        $p = Promotion::first();
        $this->assertSame(50000, $p->discount_amount);
        $this->assertNull($p->discount_percent);
    }

    public function test_muc_giam_ngoai_khoang_bi_bao_loi(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/promotions', $this->data(['discount_value' => 150]))
            ->assertSessionHasErrors('discount_value');

        $this->actingAs($this->admin)
            ->post('/admin/promotions', $this->data(['discount_type' => 'amount', 'discount_value' => 500]))
            ->assertSessionHasErrors('discount_value');
    }

    public function test_ngay_ket_thuc_truoc_ngay_bat_dau_bi_bao_loi(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/promotions', $this->data(['end_date' => today()->subDay()->toDateString()]))
            ->assertSessionHasErrors('end_date');
    }

    public function test_ma_trung_khong_phan_biet_hoa_thuong(): void
    {
        $this->promo(['code' => 'KHAITRUONG']);

        $this->actingAs($this->admin)
            ->post('/admin/promotions', $this->data(['code' => 'KhaiTruong']))
            ->assertSessionHasErrors('code');
    }

    public function test_bat_tat_ma(): void
    {
        $p = $this->promo();

        $this->actingAs($this->admin)->patch("/admin/promotions/{$p->id}/toggle");

        $this->assertFalse($p->fresh()->is_active);
    }

    public function test_trang_thai_tinh_theo_ngay(): void
    {
        $this->assertSame('active', $this->promo(['code' => 'A'])->state());
        $this->assertSame('scheduled', $this->promo(['code' => 'B', 'start_date' => today()->addDay()])->state());
        $this->assertSame('expired', $this->promo(['code' => 'C', 'start_date' => today()->subDays(9), 'end_date' => today()->subDay()])->state());
        $this->assertSame('disabled', $this->promo(['code' => 'D', 'is_active' => false])->state());
        // ngày kết thúc vẫn còn hiệu lực
        $this->assertSame('active', $this->promo(['code' => 'E', 'end_date' => today()])->state());
    }

    public function test_tinh_tien_giam_khong_vuot_qua_tien_thue(): void
    {
        $this->assertSame(15000, $this->promo(['code' => 'P10'])->discountFor(150000));
        $this->assertSame(150000, $this->promo([
            'code' => 'BIG', 'discount_percent' => null, 'discount_amount' => 200000,
        ])->discountFor(150000));
    }

    public function test_loc_theo_trang_thai(): void
    {
        $this->promo(['code' => 'DANGCHAY']);
        $this->promo(['code' => 'DAHET', 'start_date' => today()->subDays(9), 'end_date' => today()->subDay()]);

        $this->actingAs($this->admin)
            ->get('/admin/promotions?state=active')
            ->assertOk()
            ->assertSee('DANGCHAY')
            ->assertDontSee('DAHET');
    }
}
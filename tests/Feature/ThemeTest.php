<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_trang_khach_mac_dinh_toi_va_co_nut_doi_che_do(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-area-default="dark"', false)
            ->assertSee('data-theme-toggle', false);
    }

    public function test_man_hinh_mo_dau_trang_chu_doi_theo_che_do(): void
    {
        // màn hình mở đầu có cảnh ngày/đêm nên theo chế độ chung, không bị ép tối;
        // header nằm trên nó cũng vậy
        $this->get('/')
            ->assertOk()
            ->assertSee('<section id="hero" class=', false)
            ->assertDontSee('<section id="hero" data-theme="dark"', false)
            ->assertDontSee('overHero', false);
    }

    public function test_the_form_dang_nhap_theo_che_do_chung(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('data-theme-follow', false);
    }

    public function test_khu_quan_ly_mac_dinh_sang_va_thanh_ben_luon_toi(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('data-area-default="light"', false)
            ->assertSee('<aside data-theme="dark"', false);
    }
}

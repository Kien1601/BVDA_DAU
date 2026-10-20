<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginCodeMail;
use App\Models\User;
use App\Services\Otp\SmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    private object $sms;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        // SMS giả: giữ lại tin nhắn để test đọc mã
        $this->sms = new class implements SmsSender {
            public array $messages = [];

            public function send(string $phone, string $message): void
            {
                $this->messages[] = ['phone' => $phone, 'message' => $message];
            }
        };
        $this->app->instance(SmsSender::class, $this->sms);
    }

    private function customer(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'phone' => '0905123456',
            'is_active' => true,
        ], $attrs));
    }

    private function smsCode(): string
    {
        $last = end($this->sms->messages);
        preg_match('/\d{6}/', $last['message'], $m);

        return $m[0];
    }

    private function wrongCode(): string
    {
        return $this->smsCode() === '000000' ? '111111' : '000000';
    }

    private function mailCode(): string
    {
        $code = '';
        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    private function requestCode(string $identifier)
    {
        return $this->post('/login', ['identifier' => $identifier]);
    }

    private function submitCode(string $code)
    {
        return $this->post('/login/code', ['code' => $code]);
    }

    public function test_trang_dang_nhap_hien_o_nhap_email_hoac_sdt(): void
    {
        $this->get('/login')->assertOk()->assertSee('Email hoặc số điện thoại');
    }

    public function test_khach_cu_dang_nhap_bang_sdt(): void
    {
        $user = $this->customer();

        $this->requestCode('0905 123 456')->assertRedirect(route('otp.verify'));
        $this->assertSame('0905123456', $this->sms->messages[0]['phone']);

        $this->submitCode($this->smsCode())->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_khach_cu_dang_nhap_bang_email(): void
    {
        $user = $this->customer(['email' => 'khach@example.com']);

        $this->requestCode('Khach@Example.com');
        $this->submitCode($this->mailCode())->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_nhap_sai_ma_bao_so_lan_con_lai(): void
    {
        $this->customer();
        $this->requestCode('0905123456');

        $this->submitCode($this->wrongCode())
            ->assertSessionHasErrors(['code' => 'Mã không đúng. Còn 4 lần thử.']);
        $this->assertGuest();
    }

    public function test_sai_5_lan_thi_ma_bi_huy(): void
    {
        $this->customer();
        $this->requestCode('0905123456');
        $code = $this->smsCode();

        for ($i = 0; $i < 5; $i++) {
            $this->submitCode($this->wrongCode());
        }

        $this->submitCode($code)->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_ma_het_han_sau_5_phut(): void
    {
        $this->customer();
        $this->requestCode('0905123456');

        $this->travel(6)->minutes();

        $this->submitCode($this->smsCode())->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_gui_lai_ma_phai_cho_60_giay_va_ma_cu_bi_huy(): void
    {
        $user = $this->customer();
        $this->requestCode('0905123456');
        $oldCode = $this->smsCode();

        $this->post('/login/code/resend')->assertSessionHasErrors('identifier');
        $this->assertCount(1, $this->sms->messages);

        $this->travel(61)->seconds();
        $this->post('/login/code/resend')->assertSessionHasNoErrors();
        $this->assertCount(2, $this->sms->messages);

        if ($oldCode !== $this->smsCode()) {
            $this->submitCode($oldCode)->assertSessionHasErrors('code');
        }
        $this->submitCode($this->smsCode())->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_khach_moi_dung_email_bo_sung_ten_va_sdt(): void
    {
        $this->requestCode('moi@example.com');
        $this->submitCode($this->mailCode())->assertRedirect(route('otp.profile'));
        $this->assertGuest();

        $this->post('/login/profile', ['name' => 'Nguyễn Văn A', 'phone' => '+84 912 345 678'])
            ->assertRedirect('/');

        $user = User::where('email', 'moi@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('0912345678', $user->phone);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_khach_moi_dung_sdt_chi_can_ten(): void
    {
        $this->requestCode('0987654321');
        $this->submitCode($this->smsCode())->assertRedirect(route('otp.profile'));

        $this->post('/login/profile', ['name' => 'Trần B'])->assertRedirect('/');

        $user = User::where('phone', '0987654321')->first();
        $this->assertNull($user->email);
        $this->assertAuthenticatedAs($user);
    }

    public function test_bo_sung_sdt_da_co_tai_khoan_thi_bao_loi(): void
    {
        $this->customer(['phone' => '0912345678']);

        $this->requestCode('moi@example.com');
        $this->submitCode($this->mailCode());

        $this->post('/login/profile', ['name' => 'C', 'phone' => '0912345678'])
            ->assertSessionHasErrors('phone');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'moi@example.com']);
    }

    public function test_chua_xac_minh_thi_khong_vao_duoc_trang_bo_sung(): void
    {
        $this->get('/login/profile')->assertRedirect(route('login'));
        $this->post('/login/profile', ['name' => 'X', 'phone' => '0912345678'])->assertRedirect(route('login'));

        $this->assertDatabaseCount('users', 0);
    }

    public function test_tai_khoan_bi_khoa_khong_nhan_duoc_ma(): void
    {
        $this->customer(['is_active' => false]);

        $this->requestCode('0905123456')->assertSessionHasErrors('identifier');
        $this->assertCount(0, $this->sms->messages);
    }

    public function test_nhan_vien_nhap_email_o_trang_khach_chuyen_sang_dang_nhap_mat_khau(): void
    {
        User::factory()->create(['role' => 'staff', 'email' => 'staff@example.com', 'is_active' => true]);

        $this->requestCode('staff@example.com')
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasInput('email', 'staff@example.com');

        Mail::assertNothingSent();
    }

    public function test_nhan_vien_dang_nhap_bang_mat_khau_o_trang_quan_ly(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);

        $this->post('/admin/login', ['email' => $staff->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($staff);
    }

    public function test_khach_khong_dang_nhap_duoc_o_trang_quan_ly(): void
    {
        $this->customer(['email' => 'khach@example.com']);

        $this->post('/admin/login', ['email' => 'khach@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_chua_dang_nhap_vao_admin_chuyen_ve_trang_dang_nhap_quan_ly(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_trang_dang_ky_va_quen_mat_khau_da_go(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
    }
}
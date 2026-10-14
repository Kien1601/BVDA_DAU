<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\RentalOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->freezeTime();

        // Khóa giả, test không dùng khóa trong .env
        config([
            'payment.vnpay.tmn_code' => 'TESTCODE',
            'payment.vnpay.hash_secret' => 'vnpay-secret',
            'payment.vnpay.url' => 'https://vnpay.test/pay',
            'payment.momo.partner_code' => 'MOMO',
            'payment.momo.access_key' => 'momo-access',
            'payment.momo.secret_key' => 'momo-secret',
            'payment.momo.endpoint' => 'https://momo.test/create',
        ]);
    }

    private function taoDon(): RentalOrder
    {
        return RentalOrder::create([
            'user_id' => User::factory()->create(['role' => 'customer'])->id,
            'vehicle_id' => Vehicle::factory()->create(['status' => 'available'])->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(25),
            'status' => OrderStatus::Pending,
            'subtotal_amount' => 200000,
            'discount_amount' => 0,
            'total_amount' => 200000,
            'deposit_amount' => 100000,
            'surcharge_amount' => 0,
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    public function test_trang_don_hien_cac_nut_thanh_toan_coc(): void
    {
        $don = $this->taoDon();

        $this->actingAs($don->user)->get(route('booking.show', $don))
            ->assertOk()
            ->assertSee(route('payment.vnpay.create', $don))
            ->assertSee(route('payment.momo.create', $don))
            ->assertSee(route('payment.cash', $don));
    }

    private function taoKhoanCho(PaymentMethod $method): Payment
    {
        return app(PaymentService::class)->startOnlineDeposit($this->taoDon(), $method);
    }

    /** Dữ liệu VNPay gửi về, ký bằng khóa giả */
    private function vnpay(Payment $p, array $doi = []): array
    {
        $params = array_merge([
            'vnp_Amount' => $p->amount * 100,
            'vnp_TmnCode' => 'TESTCODE',
            'vnp_TxnRef' => $p->txn_ref,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TransactionNo' => '14000001',
        ], $doi);
        ksort($params);
        $params['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($params), 'vnpay-secret');

        return $params;
    }

    /** Dữ liệu MoMo gửi về, ký bằng khóa giả */
    private function momo(Payment $p, array $doi = []): array
    {
        $data = array_merge([
            'partnerCode' => 'MOMO',
            'orderId' => $p->txn_ref,
            'requestId' => $p->txn_ref,
            'amount' => $p->amount,
            'orderInfo' => 'Coc don thue',
            'orderType' => 'momo_wallet',
            'transId' => 3000001,
            'resultCode' => 0,
            'message' => 'Thanh cong',
            'payType' => 'napas',
            'responseTime' => 1700000000000,
            'extraData' => '',
        ], $doi);

        $raw = 'accessKey=momo-access';
        foreach (['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode',
            'payType', 'requestId', 'responseTime', 'resultCode', 'transId'] as $key) {
            $raw .= '&'.$key.'='.$data[$key];
        }
        $data['signature'] = hash_hmac('sha256', $raw, 'momo-secret');

        return $data;
    }

    public function test_link_vnpay_dung_so_tien_va_gio_viet_nam(): void
    {
        $don = $this->taoDon();

        $res = $this->actingAs($don->user)->post(route('payment.vnpay.create', $don));

        $link = $res->headers->get('Location');
        $this->assertStringStartsWith('https://vnpay.test/pay?', $link);
        $this->assertStringContainsString('vnp_Amount=10000000', $link);
        $this->assertStringContainsString('vnp_CreateDate='.now('Asia/Ho_Chi_Minh')->format('YmdHis'), $link);
        $this->assertStringContainsString('vnp_SecureHash=', $link);
    }

    public function test_vnpay_tra_ve_dung_chu_ky_thi_ghi_nhan_da_tra(): void
    {
        $coc = $this->taoKhoanCho(PaymentMethod::Vnpay);

        $this->get(route('payment.vnpay.return', $this->vnpay($coc)))
            ->assertRedirect(route('booking.show', $coc->rental_order_id));

        $coc->refresh();
        $this->assertSame(PaymentStatus::Paid, $coc->status);
        $this->assertSame('14000001', $coc->provider_txn_id);
    }

    public function test_vnpay_sai_chu_ky_bi_tu_choi(): void
    {
        $coc = $this->taoKhoanCho(PaymentMethod::Vnpay);
        $params = $this->vnpay($coc);
        $params['vnp_SecureHash'] = str_repeat('0', 128);

        $this->get(route('payment.vnpay.ipn', $params))->assertJson(['RspCode' => '97']);

        $this->assertSame(PaymentStatus::Pending, $coc->refresh()->status);
    }

    public function test_vnpay_sai_so_tien_bi_tu_choi(): void
    {
        $coc = $this->taoKhoanCho(PaymentMethod::Vnpay);

        $this->get(route('payment.vnpay.ipn', $this->vnpay($coc, ['vnp_Amount' => 100])))
            ->assertJson(['RspCode' => '04']);

        $this->assertSame(PaymentStatus::Pending, $coc->refresh()->status);
    }

    public function test_vnpay_ipn_goi_lai_bao_da_xac_nhan(): void
    {
        $coc = $this->taoKhoanCho(PaymentMethod::Vnpay);
        $params = $this->vnpay($coc);

        $this->get(route('payment.vnpay.ipn', $params))->assertJson(['RspCode' => '00']);
        $this->get(route('payment.vnpay.ipn', $params))->assertJson(['RspCode' => '02']);

        $this->assertSame(100000, $coc->order->paidAmount());
    }

    public function test_vnpay_khach_huy_giao_dich_thi_that_bai(): void
    {
        $coc = $this->taoKhoanCho(PaymentMethod::Vnpay);

        $this->get(route('payment.vnpay.return', $this->vnpay($coc, ['vnp_ResponseCode' => '24', 'vnp_TransactionStatus' => '02'])));

        $this->assertSame(PaymentStatus::Failed, $coc->refresh()->status);
    }

    public function test_momo_tao_link_chuyen_khach_sang_payurl(): void
    {
        Http::fake(['momo.test/*' => Http::response(['resultCode' => 0, 'payUrl' => 'https://momo.test/pay/abc'])]);
        $don = $this->taoDon();

        $this->actingAs($don->user)
            ->post(route('payment.momo.create', $don))
            ->assertRedirect('https://momo.test/pay/abc');
    }

    public function test_momo_loi_tao_link_thi_bao_loi_khong_sap_trang(): void
    {
        Http::fake(['momo.test/*' => Http::response(['resultCode' => 98, 'message' => 'Loi thu'])]);
        $don = $this->taoDon();

        $this->actingAs($don->user)
            ->post(route('payment.momo.create', $don))
            ->assertSessionHasErrors('payment');

        $this->assertSame(PaymentStatus::Failed, $don->payments()->first()->status);
    }

    public function test_momo_ipn_dung_chu_ky_thi_ghi_nhan_da_tra(): void
    {
        $coc = $this->taoKhoanCho(PaymentMethod::Momo);

        $this->postJson(route('payment.momo.ipn'), $this->momo($coc))->assertNoContent();

        $coc->refresh();
        $this->assertSame(PaymentStatus::Paid, $coc->status);
        $this->assertSame('3000001', $coc->provider_txn_id);
    }

    public function test_momo_sai_chu_ky_khong_ghi_nhan(): void
    {
        $coc = $this->taoKhoanCho(PaymentMethod::Momo);
        $data = $this->momo($coc);
        $data['signature'] = 'sai';

        $this->postJson(route('payment.momo.ipn'), $data)->assertNoContent();

        $this->assertSame(PaymentStatus::Pending, $coc->refresh()->status);
    }
}
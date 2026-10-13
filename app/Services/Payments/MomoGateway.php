<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class MomoGateway
{
    public function __construct(private PaymentService $payments)
    {
    }

    /** Gọi API MoMo để lấy link thanh toán */
    public function createPaymentUrl(Payment $payment): string
    {
        $c = config('payment.momo');

        $data = [
            'partnerCode' => $c['partner_code'],
            'requestId' => $payment->txn_ref,
            'amount' => (string) $payment->amount,
            'orderId' => $payment->txn_ref,
            'orderInfo' => 'Coc don thue '.$payment->rental_order_id,
            'redirectUrl' => route('payment.momo.return'),
            'ipnUrl' => route('payment.momo.ipn'),
            'requestType' => $c['request_type'],
            'extraData' => '',
        ];

        // Thứ tự khóa theo đúng tài liệu MoMo
        $raw = 'accessKey='.$c['access_key']
            .'&amount='.$data['amount']
            .'&extraData='.$data['extraData']
            .'&ipnUrl='.$data['ipnUrl']
            .'&orderId='.$data['orderId']
            .'&orderInfo='.$data['orderInfo']
            .'&partnerCode='.$data['partnerCode']
            .'&redirectUrl='.$data['redirectUrl']
            .'&requestId='.$data['requestId']
            .'&requestType='.$data['requestType'];

        $data['signature'] = hash_hmac('sha256', $raw, $c['secret_key']);
        $data['lang'] = 'vi';
        $data['orderExpireTime'] = config('payment.expire_minutes');

        $res = Http::timeout(30)->post($c['endpoint'], $data)->json() ?? [];

        if (($res['resultCode'] ?? -1) !== 0 || empty($res['payUrl'])) {
            $this->payments->markFailed($payment, $res);

            throw ValidationException::withMessages([
                'payment' => 'Không tạo được giao dịch MoMo: '.($res['message'] ?? 'lỗi kết nối'),
            ]);
        }

        return $res['payUrl'];
    }

    /**
     * Xử lý dữ liệu MoMo gửi về, trang trả về và IPN dùng chung.
     * Trả về [hợp lệ hay không, Payment|null].
     */
    public function handleCallback(array $input): array
    {
        $c = config('payment.momo');

        $raw = 'accessKey='.$c['access_key'];
        foreach (['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode',
            'payType', 'requestId', 'responseTime', 'resultCode', 'transId'] as $key) {
            $raw .= '&'.$key.'='.($input[$key] ?? '');
        }

        if (! hash_equals(hash_hmac('sha256', $raw, $c['secret_key']), (string) ($input['signature'] ?? ''))) {
            return [false, null];
        }

        $payment = Payment::where('txn_ref', $input['orderId'] ?? '')
            ->where('method', PaymentMethod::Momo->value)
            ->first();

        if (! $payment || (int) ($input['amount'] ?? 0) !== $payment->amount) {
            return [false, $payment];
        }

        if ($payment->status === PaymentStatus::Pending) {
            if ((string) ($input['resultCode'] ?? '') === '0') {
                $this->payments->markPaid($payment, (string) ($input['transId'] ?? ''), $input);
            } else {
                $this->payments->markFailed($payment, $input);
            }
        }

        return [true, $payment->refresh()];
    }
}
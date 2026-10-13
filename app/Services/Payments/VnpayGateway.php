<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentService;

class VnpayGateway
{
    public function __construct(private PaymentService $payments)
    {
    }

    /** Link chuyển khách sang trang thanh toán VNPay */
    public function createPaymentUrl(Payment $payment, string $ip): string
    {
        $params = [
            'vnp_Version' => config('payment.vnpay.version'),
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => config('payment.vnpay.tmn_code'),
            'vnp_Amount' => $payment->amount * 100, // VNPay tính theo đơn vị nhỏ nhất
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $payment->txn_ref,
            'vnp_OrderInfo' => 'Coc don thue '.$payment->rental_order_id,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => route('payment.vnpay.return'),
            'vnp_IpAddr' => $ip,
            'vnp_CreateDate' => now('Asia/Ho_Chi_Minh')->format('YmdHis'),
'vnp_ExpireDate' => now('Asia/Ho_Chi_Minh')->addMinutes(config('payment.expire_minutes'))->format('YmdHis'),
        ];

        ksort($params);
        $query = http_build_query($params);

        return config('payment.vnpay.url').'?'.$query.'&vnp_SecureHash='.$this->sign($query);
    }

    /**
     * Xử lý dữ liệu VNPay gửi về, trang trả về và IPN dùng chung.
     * Trả về [RspCode cho VNPay, thông điệp, Payment|null].
     */
    public function handleCallback(array $input): array
    {
        $params = array_filter($input, fn ($key) => str_starts_with($key, 'vnp_'), ARRAY_FILTER_USE_KEY);
        $hash = (string) ($params['vnp_SecureHash'] ?? '');
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
        ksort($params);

        if (! hash_equals($this->sign(http_build_query($params)), $hash)) {
            return ['97', 'Invalid signature', null];
        }

        $payment = Payment::where('txn_ref', $params['vnp_TxnRef'] ?? '')
            ->where('method', PaymentMethod::Vnpay->value)
            ->first();

        if (! $payment) {
            return ['01', 'Order not found', null];
        }
        if ((int) ($params['vnp_Amount'] ?? 0) !== $payment->amount * 100) {
            return ['04', 'Invalid amount', $payment];
        }
        if ($payment->status !== PaymentStatus::Pending) {
            return ['02', 'Order already confirmed', $payment];
        }

        $success = ($params['vnp_ResponseCode'] ?? '') === '00'
            && ($params['vnp_TransactionStatus'] ?? '') === '00';

        if ($success) {
            $this->payments->markPaid($payment, $params['vnp_TransactionNo'] ?? null, $params);
        } else {
            $this->payments->markFailed($payment, $params);
        }

        return ['00', 'Confirm Success', $payment->refresh()];
    }

    private function sign(string $data): string
    {
        return hash_hmac('sha512', $data, (string) config('payment.vnpay.hash_secret'));
    }
}
<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\RentalOrder;
use App\Services\PaymentService;
use App\Services\Payments\VnpayGateway;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /** Khách bấm "Thanh toán cọc qua VNPay" */
    public function vnpayCreate(Request $request, RentalOrder $order, PaymentService $payments, VnpayGateway $vnpay)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $payment = $payments->startOnlineDeposit($order, PaymentMethod::Vnpay);

        return redirect()->away($vnpay->createPaymentUrl($payment, $request->ip()));
    }

    /** VNPay đưa khách quay về đây sau khi thanh toán */
    public function vnpayReturn(Request $request, VnpayGateway $vnpay)
    {
        [, , $payment] = $vnpay->handleCallback($request->query());

        return $this->backToOrder($payment);
    }

    /** VNPay gọi ngầm từ máy chủ của họ (chỉ hoạt động khi web có địa chỉ công khai) */
    public function vnpayIpn(Request $request, VnpayGateway $vnpay)
    {
        [$code, $message] = $vnpay->handleCallback($request->query());

        return response()->json(['RspCode' => $code, 'Message' => $message]);
    }

    private function backToOrder(?Payment $payment)
    {
        if (! $payment) {
            return redirect('/')->with('error', 'Không xác thực được kết quả thanh toán.');
        }

        $paid = $payment->status === PaymentStatus::Paid;

        return redirect()
            ->route('booking.show', $payment->rental_order_id)
            ->with(
                $paid ? 'success' : 'error',
                $paid ? 'Thanh toán cọc thành công.' : 'Thanh toán chưa thành công, bạn có thể thử lại.'
            );
    }
}
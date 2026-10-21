<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\RentalOrder;
use App\Services\OrderManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Enums\PaymentType;
use App\Models\Payment;
use App\Services\PaymentService;

class OrderController extends Controller
{
    public function __construct(private OrderManagementService $orders)
    {
    }

        public function index(Request $request)
    {
        $status = OrderStatus::tryFrom((string) $request->query('status'))?->value;
        $orders = $this->orders->search($status, $request->query('q'));

        return view('admin.order-management.orderList', [
            'orders' => $orders,
            'counts' => $this->orders->countByStatus(),
            'status' => $status,
            'q' => $request->query('q'),
        ]);
    }

        public function show(RentalOrder $order, PaymentService $payments)
    {
        $order->load(['user', 'vehicle', 'promotion', 'approvedBy', 'pickedUpBy', 'returnedBy', 'cancelledBy', 'payments.createdBy']);

        return view('admin.order-management.orderDetail', [
            'order' => $order,
            'lateFeeNow' => $order->status === OrderStatus::Active
                ? $this->orders->lateFee($order, now())
                : 0,
            'amountDue' => $payments->amountDue($order),
        ]);
    }

    public function approve(Request $request, RentalOrder $order)
    {
        $this->orders->approve($order, $request->user());

        return back()->with('success', "Đã duyệt đơn #{$order->id}.");
    }

    public function pickUp(Request $request, RentalOrder $order)
    {
        $this->orders->pickUp($order, $request->user());

        return back()->with('success', "Đã giao xe cho đơn #{$order->id}.");
    }

    public function extend(Request $request, RentalOrder $order)
    {
        $data = $request->validate(
            ['end_time' => ['required', 'date']],
            ['end_time.required' => 'Vui lòng chọn giờ trả mới.', 'end_time.date' => 'Giờ trả mới không hợp lệ.']
        );

        $this->orders->extend($order, Carbon::parse($data['end_time']));

        return back()->with('success', "Đã gia hạn đơn #{$order->id}.");
    }

    public function complete(Request $request, RentalOrder $order)
    {
        $data = $request->validate([
            'extra_fee' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'surcharge_note' => [
                Rule::requiredIf(fn () => (int) $request->input('extra_fee') > 0),
                'nullable', 'string', 'max:500',
            ],
        ], [
            'extra_fee.integer' => 'Phụ thu phải là số nguyên.',
            'extra_fee.min' => 'Phụ thu không được âm.',
            'surcharge_note.required' => 'Có phụ thu thêm thì phải ghi lý do.',
        ]);

        $this->orders->complete(
            $order,
            $request->user(),
            (int) ($data['extra_fee'] ?? 0),
            $data['surcharge_note'] ?? null
        );

        return back()->with('success', "Đã nhận lại xe, hoàn tất đơn #{$order->id}.");
    }

    public function cancel(Request $request, RentalOrder $order)
    {
        $data = $request->validate(
            ['cancel_reason' => ['required', 'string', 'max:255']],
            ['cancel_reason.required' => 'Vui lòng nhập lý do hủy.']
        );

        $this->orders->cancelByStaff($order, $request->user(), $data['cancel_reason']);

        return back()->with('success', "Đã hủy đơn #{$order->id}.");
    }

        /** Nhân viên thu tiền mặt: cọc hoặc phần còn lại. Số tiền hệ thống tự tính */
    public function collectCash(Request $request, RentalOrder $order, PaymentService $payments)
    {
        $data = $request->validate(['type' => ['required', Rule::in(['deposit', 'balance'])]]);
        $type = PaymentType::from($data['type']);

        $amount = $type === PaymentType::Deposit
            ? $order->deposit_amount
            : $payments->amountDue($order);

        $payments->collectCash($order, $type, $amount, $request->user(), 'Thu tiền mặt tại cửa hàng');

        return back()->with('success', 'Đã thu '.number_format($amount, 0, ',', '.').' đ tiền mặt.');
    }

    /** Nhân viên xác nhận đã hoàn tiền cho khách */
    public function confirmRefund(Request $request, RentalOrder $order, Payment $payment, PaymentService $payments)
    {
        abort_unless($payment->rental_order_id === $order->id, 404);

        $payments->confirmRefund($payment, $request->user());

        return back()->with('success', 'Đã xác nhận hoàn tiền.');
    }
}
<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\RentalOrder;
use App\Services\CustomerOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function __construct(private CustomerOrderService $orders)
    {
    }

    /** B4 - Lịch sử đơn của khách */
    public function orders(Request $request)
    {
        $status = OrderStatus::tryFrom((string) $request->query('status'))?->value;

        return view('customer.account.orderHistory', [
            'orders' => $this->orders->history($request->user(), $status),
            'counts' => $this->orders->countByStatus($request->user()),
            'status' => $status,
        ]);
    }

    /** B4 - Hóa đơn: chủ đơn hoặc nhân viên, chỉ khi đơn đã trả xe */
    public function invoice(RentalOrder $order)
    {
        Gate::authorize('view', $order);
        abort_unless($order->status === OrderStatus::Completed, 404);

            $order->load(['user', 'vehicle:id,name,license_plate,type', 'promotion:id,code']);

        return view('customer.account.invoice', ['order' => $order] + $this->orders->invoice($order));
    }
}
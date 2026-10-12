<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\BookingRequest;
use App\Models\RentalOrder;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private BookingService $booking)
    {
    }

    /** B2.1 - trang chọn thời gian */
    public function create(Vehicle $vehicle)
    {
        abort_if($vehicle->status === VehicleStatus::Maintenance, 404);
        $vehicle->load('store');

        // Mặc định: nhận xe ở giờ tròn kế tiếp sau thời gian chuẩn bị tối thiểu, thuê 1 ngày
        $start = now()->addMinutes((int) config('rental.min_lead_minutes'))->addHour()->startOfHour();
        $end = $start->copy()->addDay();

        // Các khung giờ đã có người giữ xe (chỉ hiện thời gian, không hiện thông tin khách)
        $busy = RentalOrder::where('vehicle_id', $vehicle->id)
            ->blocking()
            ->where('end_time', '>', now())
            ->orderBy('start_time')
            ->take(5)
            ->get(['start_time', 'end_time']);

        return view('customer.booking.bookingCreate', [
            'vehicle' => $vehicle,
            'defaultStart' => $start,
            'defaultEnd' => $end,
            'minStart' => now()->addMinutes((int) config('rental.min_lead_minutes')),
            'busy' => $busy,
            'holdMinutes' => (int) config('rental.hold_minutes'),
        ]);
    }

    /** B2.2 + B2.3 - báo giá trực tiếp (JSON). Lỗi nghiệp vụ tự trả về 422. */
    public function quote(BookingRequest $request, Vehicle $vehicle): JsonResponse
    {
        $quote = $this->booking->quote(
            $vehicle, $request->startTime(), $request->endTime(), $request->input('promotion_code'),
        );

        return response()->json([
            'breakdown' => $this->describe($quote),
            'subtotal' => $quote['subtotal'],
            'discount' => $quote['discount'],
            'total' => $quote['total'],
            'deposit' => $quote['deposit'],
            'promotion' => $quote['promotion']
                ? ['code' => $quote['promotion']->code, 'label' => $quote['promotion']->discountLabel()]
                : null,
        ]);
    }

    /** B2.4 - tạo đơn chờ duyệt */
    public function store(BookingRequest $request, Vehicle $vehicle)
    {
        $order = $this->booking->create(
            $request->user(), $vehicle, $request->startTime(), $request->endTime(),
            $request->input('promotion_code'), $request->input('note'),
        );

        return redirect()->route('booking.show', $order)
            ->with('success', 'Đã tạo đơn thuê. Xe được giữ cho bạn trong ' . config('rental.hold_minutes') . ' phút.');
    }

    /** Trang đơn vừa tạo (B4.2 sẽ dùng lại) */
    public function show(RentalOrder $order)
    {
        Gate::authorize('view', $order);
        $order->load('vehicle.store', 'promotion');

        $holdExpired = $order->status === OrderStatus::Pending
            && $order->expires_at && $order->expires_at->isPast();

        return view('customer.booking.bookingConfirm', [
            'order' => $order,
            'holdExpired' => $holdExpired,
            'duration' => $this->describe($this->booking->rentFor($order->vehicle, $order->start_time, $order->end_time)),
        ]);
    }

    /** B2.5 - khách hủy đơn */
    public function cancel(RentalOrder $order)
    {
        Gate::authorize('cancel', $order);
        $this->booking->cancelByCustomer($order);

        return redirect()->route('booking.show', $order)->with('success', 'Đã hủy đơn thuê.');
    }

    /** "1 tuần 2 ngày 3 giờ" */
    private function describe(array $quote): string
    {
        $parts = array_filter([
            $quote['weeks'] ? $quote['weeks'] . ' tuần' : null,
            $quote['days'] ? $quote['days'] . ' ngày' : null,
            $quote['remaining_hours'] ? $quote['remaining_hours'] . ' giờ' : null,
        ]);

        return implode(' ', $parts) ?: $quote['hours'] . ' giờ';
    }
}
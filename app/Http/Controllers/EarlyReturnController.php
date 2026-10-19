<?php

namespace App\Http\Controllers;

use App\Models\RentalOrder;
use App\Services\EarlyReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EarlyReturnController extends Controller
{
    public function __construct(private EarlyReturnService $service) {}

    public function store(Request $request, RentalOrder $order)
    {
        $this->ensureOwner($request, $order);

        $data = $request->validate([
            'early_return_at' => ['required', 'date'],
            'early_return_note' => ['nullable', 'string', 'max:500'],
        ], [
            'early_return_at.required' => 'Vui lòng chọn giờ trả xe.',
            'early_return_at.date' => 'Giờ trả xe không hợp lệ.',
            'early_return_note.max' => 'Ghi chú tối đa 500 ký tự.',
        ]);

        $this->service->request($order, Carbon::parse($data['early_return_at']), $data['early_return_note'] ?? null);

        return back()->with('success', 'Đã báo cửa hàng. Tiền thuê sẽ được tính lại theo giờ trả thực tế.');
    }

    public function destroy(Request $request, RentalOrder $order)
    {
        $this->ensureOwner($request, $order);
        $this->service->cancel($order);

        return back()->with('success', 'Đã hủy báo trả sớm.');
    }

    private function ensureOwner(Request $request, RentalOrder $order): void
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403);
    }
}
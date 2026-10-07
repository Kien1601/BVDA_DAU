<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PricingController extends Controller
{
    private const PRICE = ['required', 'integer', 'min:1000', 'max:100000000'];
    private const FIELDS = ['price_per_hour', 'price_per_day', 'price_per_week'];

    public function index(Request $request)
    {
        $type = $request->query('type');

        $vehicles = Vehicle::with('store')
            ->when($type, fn ($q, $t) => $q->where('type', $t))
            ->orderBy('type')
            ->orderBy('license_plate')
            ->get();

        // Tổng quan theo loại xe: số xe và khoảng giá theo ngày
        $summary = Vehicle::query()
            ->selectRaw('type, count(*) as total, min(price_per_day) as min_day, max(price_per_day) as max_day')
            ->groupBy('type')
            ->orderBy('type')
            ->get();

        return view('admin.pricing.pricing', compact('vehicles', 'summary', 'type'));
    }

    /** C6.1 - áp một bảng giá cho mọi xe cùng loại (không tính xe đã xóa) */
    public function updateByType(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(Vehicle::TYPES)],
            'price_per_hour' => self::PRICE,
            'price_per_day' => self::PRICE,
            'price_per_week' => self::PRICE,
        ], $this->messages());

        $count = Vehicle::where('type', $data['type'])->update(Arr::only($data, self::FIELDS));

        if ($count === 0) {
            return back()->withInput()->with('error', 'Chưa có xe nào thuộc loại ' . $data['type'] . '.');
        }

        return redirect()->route('admin.pricing', ['type' => $data['type']])
            ->with('success', "Đã áp dụng giá mới cho {$count} xe loại {$data['type']}.");
    }

    /** C6.1 - sửa giá từng xe trong bảng; chỉ lưu những xe thật sự thay đổi */
    public function updateVehicles(Request $request)
    {
        $data = $request->validate([
            'prices' => ['required', 'array'],
            'prices.*.price_per_hour' => self::PRICE,
            'prices.*.price_per_day' => self::PRICE,
            'prices.*.price_per_week' => self::PRICE,
        ], $this->messages());

        $changed = 0;

        DB::transaction(function () use ($data, &$changed) {
            // whereKey bỏ qua id lạ và xe đã xóa mềm
            Vehicle::whereKey(array_keys($data['prices']))->get()->each(function (Vehicle $vehicle) use ($data, &$changed) {
                $vehicle->fill(Arr::only($data['prices'][$vehicle->id], self::FIELDS));
                if ($vehicle->isDirty()) {
                    $vehicle->save();
                    $changed++;
                }
            });
        });

        return back()->with('success', $changed ? "Đã cập nhật giá cho {$changed} xe." : 'Không có thay đổi nào.');
    }

    private function messages(): array
    {
        return [
            'type.required' => 'Vui lòng chọn loại xe.',
            'type.in' => 'Loại xe không hợp lệ.',
            'price_per_hour.*' => 'Giá phải là số nguyên từ 1.000 đồng.',
            'price_per_day.*' => 'Giá phải là số nguyên từ 1.000 đồng.',
            'price_per_week.*' => 'Giá phải là số nguyên từ 1.000 đồng.',
            'prices.*.*' => 'Giá phải là số nguyên từ 1.000 đồng.',
        ];
    }
}
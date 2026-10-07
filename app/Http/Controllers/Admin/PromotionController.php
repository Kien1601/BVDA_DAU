<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionRequest;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q')),
            'state' => $request->query('state'),
        ];

        $promotions = Promotion::query()
            ->when($filters['q'] !== '', fn ($q) => $q->where('code', 'like', '%' . mb_strtoupper($filters['q']) . '%'))
            ->when($filters['state'], fn ($q, $state) => $q->inState($state))
            ->orderByDesc('start_date')
            ->paginate(10)
            ->withQueryString();

        return view('admin.promotion.promotionList', compact('promotions', 'filters'));
    }

    public function create()
    {
        return view('admin.promotion.promotionCreate', [
            'promotion' => new Promotion([
                'is_active' => true,
                'start_date' => today(),
                'end_date' => today()->addDays(30),
            ]),
        ]);
    }

    public function store(PromotionRequest $request)
    {
        $promotion = Promotion::create($request->promotionData());

        return redirect()->route('admin.promotions.index')->with('success', "Đã tạo mã {$promotion->code}.");
    }

    public function edit(Promotion $promotion)
    {
        return view('admin.promotion.promotionEdit', compact('promotion'));
    }

    public function update(PromotionRequest $request, Promotion $promotion)
    {
        $promotion->update($request->promotionData());

        return redirect()->route('admin.promotions.index')->with('success', "Đã cập nhật mã {$promotion->code}.");
    }

    public function toggle(Promotion $promotion)
    {
        $promotion->update(['is_active' => ! $promotion->is_active]);

        return back()->with('success', ($promotion->is_active ? 'Đã bật' : 'Đã tắt') . " mã {$promotion->code}.");
    }

    public function destroy(Promotion $promotion)
    {
        // TODO (B2): không cho xóa mã đã được dùng trong đơn thuê — khi đó chỉ được tắt
        $code = $promotion->code;
        $promotion->delete();

        return redirect()->route('admin.promotions.index')->with('success', "Đã xóa mã {$code}.");
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRequest;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q'));

        $stores = Store::query()
            ->withCount('vehicles')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('address', 'like', "%{$keyword}%"));
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.store-management.storeList', compact('stores', 'keyword'));
    }

    public function create()
    {
        return view('admin.store-management.storeCreate', ['store' => new Store()]);
    }

    public function store(StoreRequest $request)
    {
        Store::create($request->validated());

        return redirect()->route('admin.stores.index')->with('success', 'Đã thêm cửa hàng.');
    }

    public function edit(Store $store)
    {
        return view('admin.store-management.storeEdit', compact('store'));
    }

    public function update(StoreRequest $request, Store $store)
    {
        $store->update($request->validated());

        return redirect()->route('admin.stores.index')->with('success', 'Đã cập nhật cửa hàng.');
    }

    public function destroy(Store $store)
    {
        // Xe đã xóa mềm vẫn trỏ tới cửa hàng (giữ lịch sử đơn thuê), nên cũng phải tính
        if ($store->vehicles()->withTrashed()->exists()) {
            return back()->with('error', 'Không xóa được: cửa hàng vẫn còn xe. Hãy chuyển xe sang cửa hàng khác trước.');
        }

        $store->delete();

        return redirect()->route('admin.stores.index')->with('success', 'Đã xóa cửa hàng.');
    }
}
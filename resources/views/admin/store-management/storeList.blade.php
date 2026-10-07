@extends('layouts.admin')

@section('title', 'Cửa hàng')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <form method="GET" action="{{ route('admin.stores.index') }}" class="flex gap-2">
            <x-text-input name="q" :value="$keyword" placeholder="Tìm theo tên hoặc địa chỉ" class="w-72" />
            <x-secondary-button type="submit">Tìm</x-secondary-button>
        </form>

        <a href="{{ route('admin.stores.create') }}"
           class="inline-flex items-center px-4 py-2 rounded-md bg-gray-800 text-white text-sm hover:bg-gray-700">
            + Thêm cửa hàng
        </a>
    </div>

    <div class="bg-white rounded border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500">
                <tr>
                    <th class="px-4 py-2">Tên cửa hàng</th>
                    <th class="px-4 py-2">Địa chỉ</th>
                    <th class="px-4 py-2">Điện thoại</th>
                    <th class="px-4 py-2 text-right">Số xe</th>
                    <th class="px-4 py-2 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stores as $store)
                    <tr class="border-t">
                        <td class="px-4 py-2 font-medium">{{ $store->name }}</td>
                        <td class="px-4 py-2">{{ $store->address }}</td>
                        <td class="px-4 py-2">{{ $store->phone ?? '—' }}</td>
                        <td class="px-4 py-2 text-right">{{ $store->vehicles_count }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <a href="{{ route('admin.stores.edit', $store) }}" class="text-indigo-600 hover:underline">Sửa</a>

                            <form method="POST" action="{{ route('admin.stores.destroy', $store) }}" class="inline"
                                  onsubmit="return confirm('Xóa cửa hàng {{ addslashes($store->name) }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ms-3 text-red-600 hover:underline">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            {{ $keyword !== '' ? 'Không tìm thấy cửa hàng phù hợp.' : 'Chưa có cửa hàng nào.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $stores->links() }}</div>
@endsection
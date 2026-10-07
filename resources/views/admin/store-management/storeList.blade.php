@extends('layouts.admin')

@section('title', 'Cửa hàng')

@section('content')
    <x-page-header eyebrow="Danh mục" title="Cửa hàng">
        <x-slot:actions>
            <form method="GET" action="{{ route('admin.stores.index') }}" class="flex items-end gap-2">
                <x-form.input name="q" :value="$keyword" placeholder="Tìm theo tên hoặc địa chỉ" class="!mt-0 w-64" />
                <x-button variant="secondary">Tìm</x-button>
            </form>
            <x-button :href="route('admin.stores.create')">+ Thêm cửa hàng</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <th>Tên cửa hàng</th>
            <th>Địa chỉ</th>
            <th>Điện thoại</th>
            <th class="text-right">Số xe</th>
            <th class="text-right">Thao tác</th>
        </x-slot:head>

        @forelse ($stores as $store)
            <tr>
                <td class="font-normal">{{ $store->name }}</td>
                <td class="text-ink/80">{{ $store->address }}</td>
                <td class="tabular-nums text-ink/80">{{ $store->phone ?? '—' }}</td>
                <td class="text-right tabular-nums">{{ $store->vehicles_count }}</td>
                <td class="whitespace-nowrap text-right">
                    <x-button variant="link" :href="route('admin.stores.edit', $store)">Sửa</x-button>

                    <form method="POST" action="{{ route('admin.stores.destroy', $store) }}" class="ms-4 inline"
                          onsubmit="return confirm('Xóa cửa hàng {{ addslashes($store->name) }}?');">
                        @csrf
                        @method('DELETE')
                        <x-button variant="link-danger">Xóa</x-button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="py-8 text-center text-muted">
                    {{ $keyword !== '' ? 'Không tìm thấy cửa hàng phù hợp.' : 'Chưa có cửa hàng nào.' }}
                </td>
            </tr>
        @endforelse
    </x-table>

    <div class="mt-4">{{ $stores->links() }}</div>
@endsection
@extends('layouts.admin')

@section('title', 'Khuyến mãi')

@section('content')
    <x-page-header eyebrow="Chính sách giá" title="Mã khuyến mãi">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('admin.pricing')">Giá thuê</x-button>
            <x-button :href="route('admin.promotions.create')">+ Tạo mã</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('admin.promotions.index') }}"
          class="mb-5 grid grid-cols-1 items-end gap-3 sm:grid-cols-[1fr_14rem_auto]">
        <x-form.input name="q" label="Tìm mã" :value="$filters['q']" placeholder="VD: KHAI" />
        <x-form.select name="state" label="Trạng thái" :value="$filters['state']" placeholder="Tất cả"
            :options="collect(\App\Models\Promotion::STATES)->map(fn ($s) => $s[0])" />
        <div class="flex items-center gap-4">
            <x-button variant="secondary">Lọc</x-button>
            @if (array_filter($filters))
                <x-button variant="link" :href="route('admin.promotions.index')">Xóa lọc</x-button>
            @endif
        </div>
    </form>

    <x-table>
        <x-slot:head>
            <th>Mã</th>
            <th>Mức giảm</th>
            <th>Thời gian hiệu lực</th>
            <th>Trạng thái</th>
            <th class="text-right">Thao tác</th>
        </x-slot:head>

        @forelse ($promotions as $promotion)
            <tr>
                <td class="font-medium tracking-wider">{{ $promotion->code }}</td>
                <td>{{ $promotion->discountLabel() }}</td>
                <td class="tabular-nums text-ink/80">
                    {{ $promotion->start_date->format('d/m/Y') }} – {{ $promotion->end_date->format('d/m/Y') }}
                </td>
                <td>
                    <x-status-badge :tone="$promotion->stateTone()">{{ $promotion->stateLabel() }}</x-status-badge>
                </td>
                <td class="whitespace-nowrap text-right">
                    <form method="POST" action="{{ route('admin.promotions.toggle', $promotion) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <x-button variant="link">{{ $promotion->is_active ? 'Tắt' : 'Bật' }}</x-button>
                    </form>

                    <x-button variant="link" class="ms-4" :href="route('admin.promotions.edit', $promotion)">Sửa</x-button>

                    <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" class="ms-4 inline"
                          onsubmit="return confirm('Xóa mã {{ $promotion->code }}?');">
                        @csrf
                        @method('DELETE')
                        <x-button variant="link-danger">Xóa</x-button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="py-8 text-center text-muted">
                    {{ array_filter($filters) ? 'Không có mã nào phù hợp bộ lọc.' : 'Chưa có mã khuyến mãi nào.' }}
                </td>
            </tr>
        @endforelse
    </x-table>

    <div class="mt-4">{{ $promotions->links() }}</div>
@endsection
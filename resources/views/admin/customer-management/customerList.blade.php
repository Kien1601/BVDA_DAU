@extends('layouts.admin')

@php
    $tabs = ['' => 'Tất cả', 'violating' => 'Có vi phạm', 'locked' => 'Đã khóa'];
    $countKey = ['' => 'all', 'violating' => 'violating', 'locked' => 'locked'];
@endphp

@section('content')
    <x-page-header title="Khách hàng" />

    <nav class="mb-4 flex flex-wrap gap-x-6 gap-y-2 border-b border-edge text-sm">
        @foreach ($tabs as $key => $text)
            <a href="{{ route('admin.customers.index', array_filter(['filter' => $key, 'q' => $q])) }}"
               class="-mb-px border-b-2 pb-2 {{ (string) $filter === $key ? 'border-vermilion text-fg' : 'border-transparent text-fg-soft hover:text-fg' }}">
                {{ $text }} <span class="tabular-nums text-fg-muted">{{ $counts[$countKey[$key]] }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('admin.customers.index') }}" class="mb-6 flex gap-3">
        @if ($filter)
            <x-text-input type="hidden" name="filter" :value="$filter" />
        @endif
        <x-text-input name="q" :value="$q" placeholder="Tên, SĐT, email hoặc CCCD" class="w-full max-w-md" />
        <button type="submit" class="rounded-full bg-solid px-5 py-2 text-sm text-on-solid transition-colors hover:bg-vermilion hover:text-white">
            Tìm
        </button>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-edge text-left text-[11px] font-medium uppercase tracking-[.2em] text-fg-muted">
                    <th class="py-3 pr-4">Khách</th>
                    <th class="py-3 pr-4">Điện thoại</th>
                    <th class="py-3 pr-4 text-right">Số đơn</th>
                    <th class="py-3 pr-4 text-right">Vi phạm</th>
                    <th class="py-3 pr-4">Trạng thái</th>
                    <th class="py-3 pr-4">Ngày tạo</th>
                    <th class="py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr class="border-b border-edge-soft text-fg hover:bg-card-2">
                        <td class="py-3 pr-4">
                            {{ $customer->name }}
                            <div class="text-xs text-fg-soft">{{ $customer->email ?? '—' }}</div>
                        </td>
                        <td class="py-3 pr-4 tabular-nums">{{ $customer->phone ?? '—' }}</td>
                        <td class="py-3 pr-4 text-right tabular-nums">{{ $customer->rental_orders_count }}</td>
                        <td class="py-3 pr-4 text-right tabular-nums {{ $customer->violation_count >= $warnAt ? 'font-semibold text-vermilion' : '' }}">
                            {{ $customer->violation_count }}
                        </td>
                        <td class="py-3 pr-4">
                            @if ($customer->is_active)
                                <span class="text-fg-soft">Hoạt động</span>
                            @else
                                <span class="text-vermilion">Đã khóa</span>
                            @endif
                        </td>
                        <td class="py-3 pr-4 tabular-nums text-fg-soft">{{ $customer->created_at->format('d/m/Y') }}</td>
                        <td class="py-3 text-right">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="text-fg-soft hover:text-vermilion">Xem</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-fg-soft">Không có khách nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $customers->links() }}</div>
@endsection
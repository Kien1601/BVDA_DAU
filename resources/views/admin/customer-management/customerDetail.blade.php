@extends('layouts.admin')

@php
    $money = fn ($v) => number_format((int) $v, 0, ',', '.').' đ';
    $label = 'text-[11px] font-medium uppercase tracking-[.2em] text-fg-muted';
    $btn = 'rounded-full bg-solid px-5 py-2 text-sm text-on-solid transition-colors hover:bg-vermilion hover:text-white';
    $btnDanger = 'rounded-full border border-edge-strong px-5 py-2 text-sm text-fg transition-colors hover:border-vermilion hover:text-vermilion';
@endphp

@section('content')
    <x-page-header title="{{ $customer->name }}" />

    <a href="{{ route('admin.customers.index') }}" class="mb-4 inline-block text-sm text-fg-soft hover:text-vermilion">← Danh sách khách</a>

    @if (session('success'))
        <div class="mb-4 border border-edge bg-card px-4 py-3 text-sm text-fg">{{ session('success') }}</div>
    @endif
        @if ($errors->any())
        <div class="mb-4 border border-vermilion bg-card px-4 py-3 text-sm text-fg">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    @if ($customer->violation_count >= $warnAt)
        <div class="mb-4 border border-vermilion bg-card px-4 py-3 text-sm text-fg">
            Khách này đã vi phạm {{ $customer->violation_count }} lần. Cân nhắc trước khi duyệt đơn mới.
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="border border-edge bg-card p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base text-fg">Thông tin</h2>
                @if ($customer->is_active)
                    <span class="text-sm text-fg-soft">Hoạt động</span>
                @else
                    <span class="text-sm text-vermilion">Đã khóa</span>
                @endif
            </div>
            <dl class="space-y-3 text-sm">
                <div><dt class="{{ $label }}">Điện thoại</dt><dd class="text-fg tabular-nums">{{ $customer->phone ?? '—' }}</dd></div>
                <div><dt class="{{ $label }}">Email</dt><dd class="text-fg">{{ $customer->email ?? '—' }}</dd></div>
                <div><dt class="{{ $label }}">CCCD</dt><dd class="text-fg tabular-nums">{{ $customer->id_card_number ?? '—' }}</dd></div>
                <div><dt class="{{ $label }}">Ngày tạo</dt><dd class="text-fg tabular-nums">{{ $customer->created_at->format('d/m/Y') }}</dd></div>
            </dl>

            @can('customer.lock')
                <form method="POST" action="{{ route('admin.customers.toggle-lock', $customer) }}" class="mt-6">
                    @csrf
                    <button type="submit" class="{{ $customer->is_active ? $btnDanger : $btn }}">
                        {{ $customer->is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
                    </button>
                </form>
            @endcan
        </section>

        <section class="border border-edge bg-card p-5 lg:col-span-2">
            <h2 class="mb-4 text-base text-fg">Tổng quan</h2>
            <dl class="grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="{{ $label }}">Tổng đơn</dt><dd class="text-xl text-fg tabular-nums">{{ $summary['orders'] }}</dd></div>
                <div><dt class="{{ $label }}">Hoàn thành</dt><dd class="text-xl text-fg tabular-nums">{{ $summary['completed'] }}</dd></div>
                <div><dt class="{{ $label }}">Đã hủy</dt><dd class="text-xl text-fg tabular-nums">{{ $summary['cancelled'] }}</dd></div>
                <div><dt class="{{ $label }}">Đã chi</dt><dd class="text-xl text-fg tabular-nums">{{ $money($summary['spent']) }}</dd></div>
                <div><dt class="{{ $label }}">Vi phạm</dt><dd class="text-xl tabular-nums {{ $customer->violation_count >= $warnAt ? 'text-vermilion' : 'text-fg' }}">{{ $customer->violation_count }}</dd></div>
                <div><dt class="{{ $label }}">Tiền phạt</dt><dd class="text-xl text-fg tabular-nums">{{ $money($summary['fines']) }}</dd></div>
            </dl>
        </section>
    </div>

    <section class="mt-6 border border-edge bg-card p-5">
        <h2 class="mb-4 text-base text-fg">Vi phạm</h2>
                @can('violation.create')
            <form method="POST" action="{{ route('admin.customers.violations.store', $customer) }}"
                  class="mb-6 grid gap-4 border-b border-edge pb-6 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <div>
                    <x-input-label for="type" value="Loại vi phạm" />
                    <select id="type" name="type" required
                            class="mt-1 block w-full border border-edge-strong bg-card px-3 py-2 text-sm text-fg focus:border-fg focus:outline-none">
                        <option value="">Chọn loại</option>
                        @foreach ($manualTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="rental_order_id" value="Đơn liên quan (nếu có)" />
                    <select id="rental_order_id" name="rental_order_id"
                            class="mt-1 block w-full border border-edge-strong bg-card px-3 py-2 text-sm text-fg focus:border-fg focus:outline-none">
                        <option value="">Không gắn đơn</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}" @selected((string) old('rental_order_id') === (string) $order->id)>
                                #{{ $order->id }} · {{ $order->vehicle->name }} · {{ $order->start_time->format('d/m') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="fine_amount" value="Tiền phạt (đ)" />
                    <x-text-input id="fine_amount" name="fine_amount" type="number" min="0" step="1000"
                                  class="mt-1 block w-full" :value="old('fine_amount')" placeholder="0" />
                </div>
                <div>
                    <x-input-label for="occurred_at" value="Thời điểm" />
                    <x-text-input id="occurred_at" name="occurred_at" type="datetime-local"
                                  class="mt-1 block w-full" :value="old('occurred_at')" />
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <x-input-label for="description" value="Mô tả" />
                    <x-text-input id="description" name="description" maxlength="500" required
                                  class="mt-1 block w-full" :value="old('description')"
                                  placeholder="Ví dụ: Trầy yếm bên trái, phạt nguội vượt đèn đỏ ngày 10/10" />
                </div>
                <div class="flex items-end">
                    <button type="submit" class="{{ $btn }} w-full">Ghi vi phạm</button>
                </div>
            </form>
        @endcan
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-edge text-left {{ $label }}">
                        <th class="py-3 pr-4">Thời điểm</th>
                        <th class="py-3 pr-4">Loại</th>
                        <th class="py-3 pr-4">Mô tả</th>
                        <th class="py-3 pr-4">Đơn</th>
                        <th class="py-3 pr-4 text-right">Tiền phạt</th>
                        <th class="py-3 pr-4">Người ghi</th>
                        @can('violation.delete')
                            <th class="py-3"></th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($violations as $violation)
                        <tr class="border-b border-edge-soft text-fg">
                            <td class="py-3 pr-4 tabular-nums text-fg-soft">{{ $violation->occurred_at->format('H:i d/m/Y') }}</td>
                            <td class="py-3 pr-4">
                                <x-status-badge :tone="$violation->type->tone()">{{ $violation->type->label() }}</x-status-badge>
                            </td>
                            <td class="py-3 pr-4 text-fg-soft">{{ $violation->description ?? '—' }}</td>
                            <td class="py-3 pr-4">
                                @if ($violation->rentalOrder)
                                    <a href="{{ route('admin.orders.show', $violation->rentalOrder) }}" class="hover:text-vermilion">#{{ $violation->rental_order_id }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-right tabular-nums">{{ $violation->fine_amount ? $money($violation->fine_amount) : '—' }}</td>
                            <td class="py-3 pr-4 text-fg-soft">{{ $violation->recorder?->name ?? 'Hệ thống' }}</td>
                                                        @can('violation.delete')
                                <td class="py-3 text-right">
                                    <form method="POST" action="{{ route('admin.customers.violations.destroy', [$customer, $violation]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-fg-soft hover:text-vermilion">Xóa</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-fg-soft">Chưa có vi phạm nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-6 border border-edge bg-card p-5">
        <h2 class="mb-4 text-base text-fg">Đơn thuê gần đây</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-edge text-left {{ $label }}">
                        <th class="py-3 pr-4">Mã</th>
                        <th class="py-3 pr-4">Xe</th>
                        <th class="py-3 pr-4">Nhận – Trả</th>
                        <th class="py-3 pr-4 text-right">Tổng</th>
                        <th class="py-3 pr-4">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="border-b border-edge-soft text-fg hover:bg-card-2">
                            <td class="py-3 pr-4 tabular-nums">
                                <a href="{{ route('admin.orders.show', $order) }}" class="hover:text-vermilion">#{{ $order->id }}</a>
                            </td>
                            <td class="py-3 pr-4">{{ $order->vehicle->name }}</td>
                            <td class="py-3 pr-4 tabular-nums text-fg-soft">{{ $order->start_time->format('H:i d/m') }} – {{ $order->end_time->format('H:i d/m') }}</td>
                            <td class="py-3 pr-4 text-right tabular-nums">{{ $money($order->total_amount) }}</td>
                            <td class="py-3 pr-4">
                                <x-status-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-status-badge>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-fg-soft">Chưa có đơn nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
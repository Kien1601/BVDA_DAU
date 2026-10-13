@extends('layouts.admin')

@section('content')
    @php
        $money = fn ($v) => number_format((int) $v, 0, ',', '.').' đ';
        $label = 'text-[11px] font-medium uppercase tracking-[.2em] text-fg-muted';
        $input = 'w-full border border-edge-strong bg-card px-3 py-2 text-sm text-fg focus:border-fg focus:outline-none';
        $btn = 'rounded-full bg-solid px-5 py-2 text-sm text-on-solid transition-colors hover:bg-vermilion hover:text-white';
        $btnDanger = 'rounded-full border border-edge-strong px-5 py-2 text-sm text-fg transition-colors hover:border-vermilion hover:text-vermilion';
    @endphp

    <x-page-header title="Đơn #{{ $order->id }}" />

    <a href="{{ route('admin.orders.index') }}" class="mb-4 inline-block text-sm text-fg-soft hover:text-vermilion">← Danh sách đơn</a>

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

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @include('admin.order-management.partials.order-info')
            @include('admin.order-management.partials.payment-panel')
        </div>
        <div class="space-y-6">
            @include('admin.order-management.partials.action-buttons')
        </div>
    </div>
@endsection
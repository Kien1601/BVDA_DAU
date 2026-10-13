@extends('layouts.admin')

@section('content')
    <x-page-header title="Đơn thuê" />

    @if (session('success'))
        <div class="mb-4 border border-edge bg-card px-4 py-3 text-sm text-fg">{{ session('success') }}</div>
    @endif

    @include('admin.order-management.partials.order-filter')
    @include('admin.order-management.partials.order-table')

    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
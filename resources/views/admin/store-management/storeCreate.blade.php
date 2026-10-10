@extends('layouts.admin')

@section('title', 'Thêm cửa hàng')

@section('content')
    <x-page-header eyebrow="Cửa hàng" title="Thêm cửa hàng" />

    <form method="POST" action="{{ route('admin.stores.store') }}" class="rounded-lg border border-edge bg-card p-6">
        @csrf

        @include('admin.store-management.partials.store-form')

        <div class="mt-8 flex justify-end gap-3 border-t border-edge pt-5">
            <x-button variant="secondary" :href="route('admin.stores.index')">Hủy</x-button>
            <x-button>Lưu cửa hàng</x-button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/views/admin/store-management/storeManagement.form.js')
@endpush
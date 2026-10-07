@extends('layouts.admin')

@section('title', 'Sửa cửa hàng')

@section('content')
    <x-page-header eyebrow="Cửa hàng" :title="$store->name" />

    <form method="POST" action="{{ route('admin.stores.update', $store) }}" class="rounded-lg border border-ink/10 bg-white p-6">
        @csrf
        @method('PUT')

        @include('admin.store-management.partials.store-form')

        <div class="mt-8 flex justify-end gap-3 border-t border-ink/10 pt-5">
            <x-button variant="secondary" :href="route('admin.stores.index')">Hủy</x-button>
            <x-button>Cập nhật</x-button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/views/admin/store-management/storeManagement.form.js')
@endpush
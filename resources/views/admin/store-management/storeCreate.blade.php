@extends('layouts.admin')

@section('title', 'Thêm cửa hàng')

@section('content')
    <form method="POST" action="{{ route('admin.stores.store') }}" class="bg-white rounded border p-6">
        @csrf

        @include('admin.store-management.partials.store-form')

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.stores.index') }}" class="px-4 py-2 rounded-md border text-sm">Hủy</a>
            <x-primary-button>Lưu</x-primary-button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/views/admin/store-management/storeManagement.form.js')
@endpush
@extends('layouts.admin')

@section('title', 'Thêm xe')

@section('content')
    <x-page-header eyebrow="Xe cho thuê" title="Thêm xe" />

    <form method="POST" action="{{ route('admin.vehicles.store') }}" enctype="multipart/form-data"
          class="rounded-lg border border-edge bg-card p-6">
        @csrf

        @include('admin.vehicle-management.partials.vehicle-form')

        <div class="mt-8 flex justify-end gap-3 border-t border-edge pt-5">
            <x-button variant="secondary" :href="route('admin.vehicles.index')">Hủy</x-button>
            <x-button>Lưu xe</x-button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/views/admin/vehicle-management/vehicleManagement.form.js')
@endpush
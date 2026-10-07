@extends('layouts.admin')

@section('title', 'Sửa xe')

@section('content')
    <x-page-header eyebrow="Xe cho thuê" :title="$vehicle->license_plate . ' · ' . $vehicle->name" />

    <form method="POST" action="{{ route('admin.vehicles.update', $vehicle) }}" enctype="multipart/form-data"
          class="rounded-lg border border-ink/10 bg-white p-6">
        @csrf
        @method('PUT')

        @include('admin.vehicle-management.partials.vehicle-form')

        <div class="mt-8 flex justify-end gap-3 border-t border-ink/10 pt-5">
            <x-button variant="secondary" :href="route('admin.vehicles.index')">Hủy</x-button>
            <x-button>Cập nhật</x-button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/views/admin/vehicle-management/vehicleManagement.form.js')
@endpush
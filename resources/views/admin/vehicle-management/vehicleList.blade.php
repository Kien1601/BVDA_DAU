@extends('layouts.admin')

@section('title', 'Xe')

@section('content')
    <x-page-header eyebrow="Danh mục" title="Xe cho thuê">
        <x-slot:actions>
            <x-button :href="route('admin.vehicles.create')">+ Thêm xe</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('admin.vehicles.index') }}"
          class="mb-5 grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_14rem_12rem_auto]">
        <x-form.input name="q" label="Tìm kiếm" :value="$filters['q']" placeholder="Biển số, tên xe hoặc IMEI" />
        <x-form.select name="store" label="Cửa hàng" :options="$stores" :value="$filters['store']" placeholder="Tất cả cửa hàng" />
        <x-form.select name="status" label="Trạng thái"
            :options="collect(\App\Enums\VehicleStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])"
            :value="$filters['status']" placeholder="Tất cả" />
        <div class="flex items-center gap-4">
            <x-button variant="secondary">Lọc</x-button>
            @if (array_filter($filters))
                <x-button variant="link" :href="route('admin.vehicles.index')">Xóa lọc</x-button>
            @endif
        </div>
    </form>

    @include('admin.vehicle-management.partials.vehicle-table')

    <div class="mt-4">{{ $vehicles->links() }}</div>
@endsection
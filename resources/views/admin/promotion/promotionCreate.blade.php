@extends('layouts.admin')

@section('title', 'Tạo mã khuyến mãi')

@section('content')
    <x-page-header eyebrow="Khuyến mãi" title="Tạo mã khuyến mãi" />

    <form method="POST" action="{{ route('admin.promotions.store') }}" class="max-w-3xl rounded-lg border border-edge bg-card p-6">
        @csrf

        @include('admin.promotion.partials.promotion-form')

        <div class="mt-8 flex justify-end gap-3 border-t border-edge pt-5">
            <x-button variant="secondary" :href="route('admin.promotions.index')">Hủy</x-button>
            <x-button>Tạo mã</x-button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/views/admin/promotion/promotion.page.js')
@endpush
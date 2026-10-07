@extends('layouts.admin')

@section('title', 'Sửa mã khuyến mãi')

@section('content')
    <x-page-header eyebrow="Khuyến mãi" :title="$promotion->code" />

    <form method="POST" action="{{ route('admin.promotions.update', $promotion) }}" class="max-w-3xl rounded-lg border border-ink/10 bg-white p-6">
        @csrf
        @method('PUT')

        @include('admin.promotion.partials.promotion-form')

        <div class="mt-8 flex justify-end gap-3 border-t border-ink/10 pt-5">
            <x-button variant="secondary" :href="route('admin.promotions.index')">Hủy</x-button>
            <x-button>Cập nhật</x-button>
        </div>
    </form>
@endsection

@push('scripts')
    @vite('resources/views/admin/promotion/promotion.page.js')
@endpush
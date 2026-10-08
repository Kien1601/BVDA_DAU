@extends('layouts.customer')

@section('title', 'Xe cho thuê')

@section('content')
    <section class="mx-auto max-w-7xl px-5 pb-24 pt-32 lg:px-8">
        <div data-reveal class="flex items-center gap-2.5 text-[10px] font-medium uppercase tracking-label text-bone-dim">
            <span class="h-1.5 w-1.5 rounded-full bg-vermilion"></span>Đội xe
        </div>
        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <h1 data-reveal style="--reveal-delay: 80ms" class="text-4xl font-normal uppercase tracking-tight sm:text-5xl">Xe cho thuê</h1>
            <p data-reveal style="--reveal-delay: 160ms" class="text-sm tabular-nums text-bone-dim">{{ $vehicles->total() }} xe phù hợp</p>
        </div>

        @include('customer.vehicle-list.partials.filter-form')
        @include('customer.vehicle-list.partials.vehicle-list')
    </section>
@endsection
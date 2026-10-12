@extends('layouts.customer')

@section('title', 'Đặt thuê ' . $vehicle->name)

@section('content')
    <section class="mx-auto max-w-7xl px-5 pb-24 pt-28 lg:px-8">
        <nav class="text-[10px] font-medium uppercase tracking-label text-fg-muted" aria-label="Đường dẫn">
            <a href="{{ route('vehicles.show', $vehicle) }}" class="transition-colors hover:text-fg">{{ $vehicle->name }}</a>
            <span class="mx-2">/</span>
            <span class="text-fg-soft">Đặt thuê</span>
        </nav>

        <h1 class="mt-6 text-4xl font-normal uppercase tracking-tight sm:text-5xl">Đặt thuê xe</h1>

        @if ($errors->has('vehicle'))
            <p class="mt-6 rounded-md border border-vermilion/40 bg-vermilion/10 px-4 py-3 text-sm text-fg">{{ $errors->first('vehicle') }}</p>
        @endif

        <form method="POST" action="{{ route('booking.store', $vehicle) }}"
              data-booking-form data-quote-url="{{ route('booking.quote', $vehicle) }}"
              class="group mt-10 grid gap-10 lg:grid-cols-[1fr_24rem]">
            @csrf

            <div class="space-y-10">
                @include('customer.booking.partials.time-picker')
                @include('customer.booking.partials.promo-code-input')

                <section>
                    <div class="mb-4 text-[10px] font-medium uppercase tracking-label text-fg-muted">03 · Ghi chú</div>
                    <label for="note" class="block text-[10px] font-medium uppercase tracking-label text-fg-muted">Ghi chú cho cửa hàng (không bắt buộc)</label>
                    <textarea id="note" name="note" rows="3" maxlength="500"
                              class="mt-1.5 block w-full rounded-md border border-edge-strong bg-card px-3 py-2 text-sm text-fg placeholder:text-fg-muted focus:border-fg/50 focus:outline-none focus:ring-0"
                              placeholder="VD: cần thêm mũ bảo hiểm trẻ em">{{ old('note') }}</textarea>
                    @error('note')
                        <p class="mt-1 text-xs text-vermilion">{{ $message }}</p>
                    @enderror
                </section>
            </div>

            @include('customer.booking.partials.price-summary')
        </form>
    </section>
@endsection

@push('scripts')
    @vite('resources/views/customer/booking/booking.page.js')
@endpush
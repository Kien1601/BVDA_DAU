@if ($vehicles->isEmpty())
    <div class="mt-16 rounded-xl border border-dashed border-line px-6 py-16 text-center">
        <p class="text-lg text-bone">Không tìm thấy xe phù hợp.</p>
        <p class="mt-2 text-sm text-muted">Thử bỏ bớt điều kiện lọc hoặc chọn cửa hàng khác.</p>
        @if ($hasFilters)
            <div class="mt-6">
                <x-button variant="outline-light" :href="route('vehicles.index')">Xem tất cả xe</x-button>
            </div>
        @endif
    </div>
@else
    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($vehicles as $i => $vehicle)
            <div data-reveal style="--reveal-delay: {{ ($i % 3) * 90 }}ms">
                <x-vehicle-card :vehicle="$vehicle" />
            </div>
        @endforeach
    </div>

    <div class="mt-12">{{ $vehicles->links('components.pagination') }}</div>
@endif
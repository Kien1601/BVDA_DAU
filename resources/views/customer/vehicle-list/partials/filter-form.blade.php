<form method="GET" action="{{ route('vehicles.index') }}"
      class="mt-10 grid gap-4 rounded-xl border border-edge bg-card p-5 sm:grid-cols-2 lg:grid-cols-[1fr_10rem_13rem_11rem_11rem_auto] lg:items-end">
    <x-form.input tone="dark" name="q" label="Tìm xe" :value="$filters['q']" placeholder="Tên xe hoặc hãng" />

    <x-form.select tone="dark" name="type" label="Loại xe" :value="$filters['type']" placeholder="Tất cả"
        :options="array_combine(\App\Models\Vehicle::TYPES, \App\Models\Vehicle::TYPES)" />

    <x-form.select tone="dark" name="store" label="Cửa hàng" :value="$filters['store']" placeholder="Tất cả"
        :options="$stores" />

    <x-form.select tone="dark" name="max_price" label="Giá ngày tối đa" :value="$filters['max_price']" placeholder="Không giới hạn"
        :options="[100000 => 'Đến 100.000 đ', 150000 => 'Đến 150.000 đ', 200000 => 'Đến 200.000 đ', 300000 => 'Đến 300.000 đ']" />

    <x-form.select tone="dark" name="sort" label="Sắp xếp" :value="$filters['sort']"
        :options="['newest' => 'Mới nhất', 'price_asc' => 'Giá tăng dần', 'price_desc' => 'Giá giảm dần']" />

    <div class="flex items-center gap-4">
        <x-button variant="light">Lọc</x-button>
        @if ($hasFilters)
            <a href="{{ route('vehicles.index') }}" class="text-[11px] font-medium uppercase tracking-label text-fg-soft transition-colors hover:text-fg">Xóa lọc</a>
        @endif
    </div>
</form>
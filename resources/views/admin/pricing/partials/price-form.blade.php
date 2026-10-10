<form method="POST" action="{{ route('admin.pricing.by-type') }}"
      class="mb-10 rounded-lg border border-edge bg-card p-6"
      onsubmit="return confirm('Áp dụng giá mới cho TẤT CẢ xe thuộc loại đã chọn?');">
    @csrf
    @method('PUT')

    <div class="mb-4 text-[10px] font-medium uppercase tracking-label text-fg-muted">01 · Áp dụng hàng loạt theo loại xe</div>

    <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-[12rem_1fr_1fr_1fr_auto]">
        <x-form.select name="type" label="Loại xe" :value="$type" placeholder="Chọn loại xe"
            :options="array_combine(\App\Models\Vehicle::TYPES, \App\Models\Vehicle::TYPES)" />
        <x-form.input type="number" name="price_per_hour" label="Giá giờ (đ)" min="1000" step="1000" required />
        <x-form.input type="number" name="price_per_day" label="Giá ngày (đ)" min="1000" step="1000" required />
        <x-form.input type="number" name="price_per_week" label="Giá tuần (đ)" min="1000" step="1000" required />
        <x-button>Áp dụng</x-button>
    </div>
</form>
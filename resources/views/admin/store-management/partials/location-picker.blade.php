<div>
    <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">Vị trí trên bản đồ</div>
    <p class="mt-1 text-xs text-fg-muted">Bấm lên bản đồ hoặc kéo ghim để chọn vị trí cửa hàng.</p>

    <div id="store-location-map"
         class="z-0 mt-2 h-80 rounded-lg border {{ $errors->has('latitude') ? 'border-vermilion' : 'border-edge-strong' }}"
         data-default-lat="10.7769"
         data-default-lng="106.7009"></div>

    <div class="mt-4 grid grid-cols-2 gap-4">
        <x-form.input name="latitude" label="Vĩ độ" :value="$store->latitude" readonly />
        <x-form.input name="longitude" label="Kinh độ" :value="$store->longitude" readonly />
    </div>
</div>
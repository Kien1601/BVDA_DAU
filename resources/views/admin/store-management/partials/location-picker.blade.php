<div>
    <x-input-label value="Vị trí trên bản đồ" />
    <p class="text-sm text-gray-500 mt-1">Bấm lên bản đồ hoặc kéo ghim để chọn vị trí cửa hàng.</p>

    <div id="store-location-map"
         class="mt-2 h-80 rounded border z-0"
         data-default-lat="10.7769"
         data-default-lng="106.7009"></div>

    <div class="grid grid-cols-2 gap-4 mt-3">
        <div>
            <x-input-label for="latitude" value="Vĩ độ" />
            <x-text-input id="latitude" name="latitude" class="block mt-1 w-full bg-gray-50"
                          :value="old('latitude', $store->latitude)" readonly />
        </div>
        <div>
            <x-input-label for="longitude" value="Kinh độ" />
            <x-text-input id="longitude" name="longitude" class="block mt-1 w-full bg-gray-50"
                          :value="old('longitude', $store->longitude)" readonly />
        </div>
    </div>

    <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
</div>
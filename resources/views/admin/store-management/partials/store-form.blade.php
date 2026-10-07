<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="space-y-4">
        <div>
            <x-input-label for="name" value="Tên cửa hàng" />
            <x-text-input id="name" name="name" class="block mt-1 w-full"
                          :value="old('name', $store->name)" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="address" value="Địa chỉ" />
            <x-text-input id="address" name="address" class="block mt-1 w-full"
                          :value="old('address', $store->address)" required />
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone" value="Số điện thoại" />
            <x-text-input id="phone" name="phone" class="block mt-1 w-full"
                          :value="old('phone', $store->phone)" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
    </div>

    @include('admin.store-management.partials.location-picker')
</div>
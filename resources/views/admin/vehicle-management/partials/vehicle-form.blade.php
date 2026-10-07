<div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
    <div class="space-y-8 lg:col-span-2">
        <section>
            <div class="mb-4 text-[10px] font-medium uppercase tracking-label text-muted">01 · Thông tin xe</div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-form.input name="license_plate" label="Biển số" :value="$vehicle->license_plate"
                              placeholder="VD: 59X1-123.45" required autofocus />
                <x-form.input name="name" label="Tên xe" :value="$vehicle->name" placeholder="VD: Honda Vision" required />
                <x-form.input name="brand" label="Hãng" :value="$vehicle->brand" placeholder="VD: Honda" />
                <x-form.select name="type" label="Loại xe" :value="$vehicle->type" placeholder="Chọn loại xe"
                               :options="array_combine(\App\Models\Vehicle::TYPES, \App\Models\Vehicle::TYPES)" />
                <div class="sm:col-span-2">
                    <x-form.select name="store_id" label="Cửa hàng" :value="$vehicle->store_id"
                                   :options="$stores" placeholder="Chọn cửa hàng" />
                </div>
            </div>
        </section>

        <section>
            <div class="mb-4 text-[10px] font-medium uppercase tracking-label text-muted">02 · Giá thuê (đồng)</div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <x-form.input type="number" name="price_per_hour" label="Theo giờ" :value="$vehicle->price_per_hour" min="1000" step="1000" required />
                <x-form.input type="number" name="price_per_day" label="Theo ngày" :value="$vehicle->price_per_day" min="1000" step="1000" required />
                <x-form.input type="number" name="price_per_week" label="Theo tuần" :value="$vehicle->price_per_week" min="1000" step="1000" required />
            </div>
        </section>

        <section>
            <div class="mb-4 text-[10px] font-medium uppercase tracking-label text-muted">03 · Thiết bị định vị</div>
            @include('admin.vehicle-management.partials.gps-device-field')
        </section>
    </div>

    <div class="space-y-8">
        <x-form.image-upload name="image" label="Ảnh xe" :current="$vehicle->imageUrl()" />
        @include('admin.vehicle-management.partials.status-select')
    </div>
</div>
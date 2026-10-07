<div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
    <div class="space-y-5">
        <x-form.input name="name" label="Tên cửa hàng" :value="$store->name" required autofocus />
        <x-form.input name="address" label="Địa chỉ" :value="$store->address" required />
        <x-form.input name="phone" label="Số điện thoại" :value="$store->phone" />
    </div>

    @include('admin.store-management.partials.location-picker')
</div>
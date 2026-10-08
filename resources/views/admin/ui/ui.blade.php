{{--
    Trang mẫu giao diện (chỉ máy dev): hai cột cạnh nhau, trái chế độ sáng, phải chế độ tối,
    để soát mọi component dùng chung ở cả hai chế độ cùng lúc. Dữ liệu giả, không truy vấn CSDL.
--}}
@extends('layouts.admin')

@section('title', 'Mẫu giao diện')

@section('content')
    @php
        // lỗi mẫu cho ô nhập "có lỗi" (component đọc từ $errors)
        $errors->put('default', new \Illuminate\Support\MessageBag(['ui_error' => 'Giá trị không hợp lệ (lỗi mẫu).']));

        // thông báo mẫu; xóa ở cuối trang để khối thông báo của layout không hiện lặp lại
        session()->now('success', 'Đã lưu thay đổi (thông báo mẫu).');
        session()->now('error', 'Không lưu được, vui lòng thử lại (thông báo mẫu).');

        $store = new \App\Models\Store(['name' => 'CH Quận 1', 'address' => '12 Lê Lợi, Q.1']);

        $makeVehicle = function (int $id, array $data) use ($store) {
            $vehicle = new \App\Models\Vehicle($data);
            $vehicle->id = $id;

            return $vehicle->setRelation('store', $store);
        };

        // xe "có ảnh": ghi đè imageUrl() bằng ảnh SVG nhúng sẵn, không phụ thuộc file trong storage
        $photo = 'data:image/svg+xml;charset=utf-8,' . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300">'
            . '<defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3b4a55"/><stop offset="1" stop-color="#151c22"/></linearGradient></defs>'
            . '<rect width="400" height="300" fill="url(#g)"/>'
            . '<circle cx="120" cy="215" r="42" fill="none" stroke="#dfe7e0" stroke-width="10"/>'
            . '<circle cx="290" cy="215" r="42" fill="none" stroke="#dfe7e0" stroke-width="10"/>'
            . '<path d="M120 215 L185 150 L250 150 L290 215 M185 150 L170 115 L205 115" fill="none" stroke="#e0231c" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/>'
            . '</svg>'
        );

        $withPhoto = new class extends \App\Models\Vehicle {
            public ?string $demoImage = null;

            public function imageUrl(): ?string
            {
                return $this->demoImage;
            }
        };
        $withPhoto->fill([
            'name' => 'Honda Air Blade', 'brand' => 'Honda', 'type' => 'Tay ga',
            'price_per_hour' => 30000, 'price_per_day' => 160000,
            'status' => \App\Enums\VehicleStatus::Available,
        ]);
        $withPhoto->id = 901;
        $withPhoto->demoImage = $photo;
        $withPhoto->setRelation('store', $store);

        $cards = [
            $withPhoto,
            $makeVehicle(902, [
                'name' => 'Yamaha Exciter', 'brand' => 'Yamaha', 'type' => 'Côn tay',
                'price_per_hour' => 35000, 'price_per_day' => 180000,
                'status' => \App\Enums\VehicleStatus::Rented,
            ]),
        ];
    @endphp

    <x-page-header title="Mẫu giao diện" eyebrow="Chỉ máy dev" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @foreach (['light' => 'Chế độ sáng', 'dark' => 'Chế độ tối'] as $theme => $caption)
            <div data-theme="{{ $theme }}" class="rounded-xl border border-edge bg-page p-6 text-fg">
                @include('admin.ui.partials.sample', ['theme' => $theme, 'caption' => $caption, 'cards' => $cards])
            </div>
        @endforeach
    </div>

    @php session()->forget(['success', 'error']); @endphp
@endsection

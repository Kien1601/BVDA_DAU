<?php

namespace App\Http\Requests\Admin;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền đã kiểm tra ở route
    }

    /** Biển số luôn lưu dạng chữ hoa, không có khoảng trắng thừa */
    protected function prepareForValidation(): void
    {
        if ($this->filled('license_plate')) {
            $this->merge(['license_plate' => mb_strtoupper(trim($this->license_plate))]);
        }
    }

    public function rules(): array
    {
        /** @var Vehicle|null $vehicle  null khi thêm mới */
        $vehicle = $this->route('vehicle');
        $isRented = $vehicle?->status === VehicleStatus::Rented;
        $price = ['required', 'integer', 'min:1000', 'max:100000000'];

        return [
            'store_id'       => ['required', 'exists:stores,id'],
            'license_plate'  => ['required', 'string', 'max:20',
                                 Rule::unique('vehicles', 'license_plate')->ignore($vehicle)],
            'name'           => ['required', 'string', 'max:255'],
            'brand'          => ['nullable', 'string', 'max:50'],
            'type'           => ['required', Rule::in(Vehicle::TYPES)],
            'price_per_hour' => $price,
            'price_per_day'  => $price,
            'price_per_week' => $price,
            // C2.4: để trống = gỡ thiết bị (Laravel tự đổi chuỗi rỗng thành null)
            'gps_device_id'  => ['nullable', 'digits_between:10,20',
                                 Rule::unique('vehicles', 'gps_device_id')->ignore($vehicle)],
            'image'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // C2.5: xe đang cho thuê thì không được gửi trạng thái lên
            'status'         => $isRented
                ? ['prohibited']
                : ['required', Rule::enum(VehicleStatus::class)->only(VehicleStatus::manual())],
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required'            => 'Vui lòng chọn cửa hàng.',
            'license_plate.required'       => 'Vui lòng nhập biển số.',
            'license_plate.unique'         => 'Biển số này đã có trong hệ thống.',
            'name.required'                => 'Vui lòng nhập tên xe.',
            'type.required'                => 'Vui lòng chọn loại xe.',
            'type.in'                      => 'Loại xe không hợp lệ.',
            'price_per_hour.*'             => 'Giá theo giờ phải là số nguyên từ 1.000 đồng.',
            'price_per_day.*'              => 'Giá theo ngày phải là số nguyên từ 1.000 đồng.',
            'price_per_week.*'             => 'Giá theo tuần phải là số nguyên từ 1.000 đồng.',
            'gps_device_id.digits_between' => 'Mã thiết bị chỉ gồm chữ số (10–20 số).',
            'gps_device_id.unique'         => 'Mã thiết bị này đang gắn cho xe khác.',
            'image.image'                  => 'File tải lên phải là ảnh.',
            'image.mimes'                  => 'Ảnh phải có định dạng JPG, PNG hoặc WebP.',
            'image.max'                    => 'Ảnh tối đa 2 MB.',
            'status.prohibited'            => 'Xe đang cho thuê, không thể đổi trạng thái thủ công.',
            'status.*'                     => 'Chỉ được chọn trạng thái Sẵn sàng hoặc Bảo dưỡng.',
        ];
    }
}
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền store.manage đã kiểm tra ở route
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'address'   => ['required', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Vui lòng nhập tên cửa hàng.',
            'address.required'   => 'Vui lòng nhập địa chỉ.',
            'phone.max'          => 'Số điện thoại tối đa 20 ký tự.',
            'latitude.required'  => 'Vui lòng chọn vị trí cửa hàng trên bản đồ.',
            'longitude.required' => 'Vui lòng chọn vị trí cửa hàng trên bản đồ.',
            'latitude.*'         => 'Vĩ độ không hợp lệ.',
            'longitude.*'        => 'Kinh độ không hợp lệ.',
        ];
    }
}
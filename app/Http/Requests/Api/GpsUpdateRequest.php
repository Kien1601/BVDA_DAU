<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GpsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // đã xác thực bằng token ở middleware
    }

    public function rules(): array
    {
        return [
            'device.uniqueId'     => ['required', 'string', 'max:32'],
            'position.latitude'   => ['required', 'numeric', 'between:-90,90'],
            'position.longitude'  => ['required', 'numeric', 'between:-180,180'],
            'position.speed'      => ['nullable', 'numeric', 'min:0'],
            'position.deviceTime' => ['required', 'date'],
        ];
    }
}
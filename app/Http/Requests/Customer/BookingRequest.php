<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Chỉ kiểm tra định dạng. Quy tắc nghiệp vụ (trùng lịch, thời gian hợp lệ, mã khuyến mãi)
 * nằm ở BookingService, để form và API báo giá dùng chung một chỗ.
 */
class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền booking.create đã kiểm tra ở route
    }

    public function rules(): array
    {
        return [
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date'],
            'promotion_code' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.*' => 'Vui lòng chọn thời gian nhận xe hợp lệ.',
            'end_time.*' => 'Vui lòng chọn thời gian trả xe hợp lệ.',
            'promotion_code.max' => 'Mã khuyến mãi tối đa 30 ký tự.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ];
    }

    /** Ô datetime-local gửi "2026-10-21T09:00", hiểu theo giờ Việt Nam (APP_TIMEZONE) */
    public function startTime(): Carbon
    {
        return Carbon::parse($this->input('start_time'))->seconds(0);
    }

    public function endTime(): Carbon
    {
        return Carbon::parse($this->input('end_time'))->seconds(0);
    }
}
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền pricing.manage đã kiểm tra ở route
    }

    /** Mã luôn lưu chữ hoa: "khaitruong" và "KHAITRUONG" là một mã */
    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge(['code' => mb_strtoupper(trim($this->code))]);
        }
    }

    public function rules(): array
    {
        $isPercent = $this->input('discount_type') === 'percent';

        return [
            'code' => ['required', 'regex:/^[A-Z0-9_-]{3,30}$/',
                       Rule::unique('promotions', 'code')->ignore($this->route('promotion'))],
            'discount_type' => ['required', Rule::in(['percent', 'amount'])],
            'discount_value' => ['required', 'integer',
                                 Rule::when($isPercent, ['between:1,100'], ['min:1000', 'max:10000000'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã.',
            'code.regex' => 'Mã gồm 3–30 ký tự: chữ không dấu, số, gạch ngang hoặc gạch dưới.',
            'code.unique' => 'Mã này đã tồn tại.',
            'discount_type.*' => 'Vui lòng chọn hình thức giảm.',
            'discount_value.required' => 'Vui lòng nhập mức giảm.',
            'discount_value.integer' => 'Mức giảm phải là số nguyên.',
            'discount_value.between' => 'Giảm theo phần trăm phải từ 1 đến 100.',
            'discount_value.min' => 'Giảm theo số tiền phải từ 1.000 đồng.',
            'discount_value.max' => 'Giảm theo số tiền tối đa 10.000.000 đồng.',
            'start_date.*' => 'Ngày bắt đầu không hợp lệ.',
            'end_date.after_or_equal' => 'Ngày kết thúc không được trước ngày bắt đầu.',
            'end_date.*' => 'Ngày kết thúc không hợp lệ.',
        ];
    }

    /** Dữ liệu sẵn sàng để lưu: chỉ một trong hai cột mức giảm có giá trị */
    public function promotionData(): array
    {
        $v = $this->validated();
        $isPercent = $v['discount_type'] === 'percent';

        return [
            'code' => $v['code'],
            'discount_percent' => $isPercent ? (int) $v['discount_value'] : null,
            'discount_amount' => $isPercent ? null : (int) $v['discount_value'],
            'start_date' => $v['start_date'],
            'end_date' => $v['end_date'],
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
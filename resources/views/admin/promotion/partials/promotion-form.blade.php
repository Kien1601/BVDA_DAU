@php
    $discountType = $promotion->discount_amount !== null ? 'amount' : 'percent';
    $discountValue = $promotion->discount_amount ?? $promotion->discount_percent;
@endphp

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-form.input name="code" label="Mã khuyến mãi" :value="$promotion->code"
                      class="uppercase tracking-wider" placeholder="VD: KHAITRUONG" required autofocus
                      hint="3–30 ký tự: chữ không dấu, số, gạch ngang hoặc gạch dưới. Tự đổi sang chữ hoa." />
    </div>

    <x-form.select name="discount_type" label="Hình thức giảm" :value="$discountType" data-discount-type
        :options="['percent' => 'Giảm theo phần trăm (%)', 'amount' => 'Giảm số tiền cố định (đ)']" />

    <x-form.input type="number" name="discount_value" label="Mức giảm" :value="$discountValue"
                  min="1" required data-discount-value
                  hint="Phần trăm: 1–100. Số tiền: từ 1.000 đồng." />

    <x-form.input type="date" name="start_date" label="Ngày bắt đầu"
                  :value="$promotion->start_date?->format('Y-m-d')" required />

    <x-form.input type="date" name="end_date" label="Ngày kết thúc"
                  :value="$promotion->end_date?->format('Y-m-d')" required
                  hint="Mã vẫn dùng được trong ngày kết thúc." />

    <label class="inline-flex items-center gap-3 text-sm sm:col-span-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1"
               class="h-4 w-4 rounded border-edge-strong bg-card text-vermilion focus:ring-vermilion/40"
               @checked(old('is_active', $promotion->is_active))>
        Bật mã (khách dùng được trong thời gian hiệu lực)
    </label>
</div>
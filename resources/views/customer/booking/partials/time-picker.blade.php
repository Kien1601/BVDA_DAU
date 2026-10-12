<section>
    <div class="mb-4 text-[10px] font-medium uppercase tracking-label text-fg-muted">01 · Thời gian thuê</div>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.input type="datetime-local" name="start_time" label="Nhận xe"
            :value="$defaultStart->format('Y-m-d\TH:i')"
            :min="$minStart->format('Y-m-d\TH:i')" required />
        <x-form.input type="datetime-local" name="end_time" label="Trả xe"
            :value="$defaultEnd->format('Y-m-d\TH:i')"
            :min="$minStart->format('Y-m-d\TH:i')" required />
    </div>

    <p class="mt-3 text-xs text-fg-muted">
        Nhận xe cách hiện tại ít nhất {{ config('rental.min_lead_minutes') }} phút, tối đa {{ config('rental.max_days') }} ngày.
        Lẻ phút được tính tròn lên một giờ.
    </p>

    @if ($busy->isNotEmpty())
        <div class="mt-5 rounded-lg border border-edge bg-card-2 p-4">
            <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">Khung giờ đã có người đặt</div>
            <ul class="mt-2 space-y-1 text-sm tabular-nums text-fg-soft">
                @foreach ($busy as $slot)
                    <li>{{ $slot->start_time->format('H:i d/m') }} → {{ $slot->end_time->format('H:i d/m') }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
{{--
    Trạng thái dạng chấm + chữ, không dùng khối màu đặc.
    tone: success | warning | danger | info | neutral
--}}
@props(['tone' => 'neutral'])

@php
    $dots = [
        'success' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'danger'  => 'bg-vermilion',
        'info'    => 'bg-sky-500',
        'neutral' => 'bg-fg-muted',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-xs text-fg-soft']) }}>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dots[$tone] ?? $dots['neutral'] }}"></span>
    {{ $slot }}
</span>
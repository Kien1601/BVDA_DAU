{{--
    Ô nhập có nhãn và thông báo lỗi.
    tone: light (khu quản lý, mặc định) | dark (trang khách)
    Hỗ trợ tên dạng mảng: prices[5][price_per_day].
--}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'tone' => 'light'])

@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', $name);
    $invalid = $errors->has($key);
    $dark = $tone === 'dark';

    $surface = $dark
        ? 'bg-ink text-bone placeholder:text-muted'
        : 'bg-white text-ink placeholder:text-muted/70 read-only:bg-paper';
    $border = $invalid
        ? 'border-vermilion focus:border-vermilion'
        : ($dark ? 'border-line focus:border-bone/40' : 'border-ink/15 focus:border-ink/50');
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="block text-[10px] font-medium uppercase tracking-label text-muted">{{ $label }}</label>
    @endif

    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($key, $value) }}"
        {{ $attributes->except('id')->merge([
            'class' => 'mt-1.5 block w-full rounded-md border px-3 py-2 text-sm shadow-none transition '
                     . 'focus:outline-none focus:ring-0 ' . $surface . ' ' . $border,
        ]) }}>

    @if ($hint && ! $invalid)
        <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
    @endif

    @error($key)
        <p class="mt-1 text-xs text-vermilion">{{ $message }}</p>
    @enderror
</div>
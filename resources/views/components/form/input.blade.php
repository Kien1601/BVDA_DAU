{{--
    Ô nhập có nhãn và thông báo lỗi.
    tone: giữ để các trang đang truyền không lỗi, không còn tác dụng (màu theo vai trò tự đổi theo chế độ).
    Hỗ trợ tên dạng mảng: prices[5][price_per_day].
--}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'tone' => 'light'])

@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', $name);
    $invalid = $errors->has($key);
    $surface = 'bg-card text-fg placeholder:text-fg-muted read-only:bg-card-2';
    $border = $invalid
        ? 'border-vermilion focus:border-vermilion'
        : 'border-edge-strong focus:border-fg/50';
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="block text-[10px] font-medium uppercase tracking-label text-fg-muted">{{ $label }}</label>
    @endif

    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($key, $value) }}"
        {{ $attributes->except('id')->merge([
            'class' => 'mt-1.5 block w-full rounded-md border px-3 py-2 text-sm shadow-none transition '
                     . 'focus:outline-none focus:ring-0 ' . $surface . ' ' . $border,
        ]) }}>

    @if ($hint && ! $invalid)
        <p class="mt-1 text-xs text-fg-muted">{{ $hint }}</p>
    @endif

    @error($key)
        <p class="mt-1 text-xs text-vermilion">{{ $message }}</p>
    @enderror
</div>
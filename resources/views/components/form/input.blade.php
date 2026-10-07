{{--
    Ô nhập có nhãn và thông báo lỗi.
    Tự lấy giá trị cũ (old) khi form bị trả về do lỗi kiểm tra dữ liệu.
--}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null])

@php
    $id = $attributes->get('id', $name);
    $invalid = $errors->has($name);
    $state = $invalid
        ? 'border-vermilion focus:border-vermilion'
        : 'border-ink/15 focus:border-ink/50';
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="block text-[10px] font-medium uppercase tracking-label text-muted">
            {{ $label }}
        </label>
    @endif

    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
        {{ $attributes->except('id')->merge([
            'class' => 'mt-1.5 block w-full rounded-md border bg-white px-3 py-2 text-sm text-ink '
                     . 'placeholder:text-muted/70 shadow-none transition focus:outline-none focus:ring-0 '
                     . 'read-only:bg-paper ' . $state,
        ]) }}>

    @if ($hint && ! $invalid)
        <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-xs text-vermilion">{{ $message }}</p>
    @enderror
</div>
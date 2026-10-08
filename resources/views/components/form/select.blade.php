{{--
    Ô chọn có nhãn và thông báo lỗi. options: [giá trị => chữ hiển thị].
    tone: giữ để các trang đang truyền không lỗi, không còn tác dụng (màu theo vai trò tự đổi theo chế độ).
--}}
@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'tone' => 'light'])

@php
    $id = $attributes->get('id', $name);
    $invalid = $errors->has($name);
    $current = (string) old($name, $value);
    $surface = 'bg-card text-fg';
    $border = $invalid
        ? 'border-vermilion focus:border-vermilion'
        : 'border-edge-strong focus:border-fg/50';
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="block text-[10px] font-medium uppercase tracking-label text-fg-muted">{{ $label }}</label>
    @endif

    <select id="{{ $id }}" name="{{ $name }}"
        {{ $attributes->except('id')->merge([
            'class' => 'mt-1.5 block w-full rounded-md border px-3 py-2 pe-9 text-sm shadow-none transition '
                     . 'focus:outline-none focus:ring-0 ' . $surface . ' ' . $border,
        ]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $key => $text)
            <option value="{{ $key }}" @selected($current === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>

    @error($name)
        <p class="mt-1 text-xs text-vermilion">{{ $message }}</p>
    @enderror
</div>
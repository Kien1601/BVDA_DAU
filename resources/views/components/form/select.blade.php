{{--
    Ô chọn có nhãn và thông báo lỗi. options: mảng [giá trị => chữ hiển thị].
--}}
@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null])

@php
    $id = $attributes->get('id', $name);
    $invalid = $errors->has($name);
    $current = (string) old($name, $value);
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

    <select id="{{ $id }}" name="{{ $name }}"
        {{ $attributes->except('id')->merge([
            'class' => 'mt-1.5 block w-full rounded-md border bg-white px-3 py-2 pe-9 text-sm text-ink '
                     . 'shadow-none transition focus:outline-none focus:ring-0 ' . $state,
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
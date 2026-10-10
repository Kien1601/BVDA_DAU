{{--
    Chọn ảnh có xem trước. Xem trước do resources/js/shared/form/imagePreview.js xử lý.
    current: URL ảnh hiện có (khi sửa).
--}}
@props(['name', 'label' => null, 'current' => null, 'hint' => 'JPG, PNG hoặc WebP, tối đa 2 MB.'])

<div data-image-upload>
    @if ($label)
        <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">{{ $label }}</div>
    @endif

    <div class="mt-1.5 overflow-hidden rounded-lg border bg-page {{ $errors->has($name) ? 'border-vermilion' : 'border-edge-strong' }}">
        <div class="aspect-[4/3]">
            <img data-preview src="{{ $current }}" alt=""
                 class="h-full w-full object-cover {{ $current ? '' : 'hidden' }}">
            <div data-placeholder
                 class="flex h-full items-center justify-center text-[10px] uppercase tracking-label text-fg-muted {{ $current ? 'hidden' : '' }}">
                Chưa có ảnh
            </div>
        </div>
    </div>

    <label class="mt-3 inline-flex cursor-pointer items-center rounded-full border border-edge-strong px-4 py-2 text-[11px] font-medium uppercase tracking-label text-fg transition-colors hover:border-fg/40">
        {{ $current ? 'Đổi ảnh' : 'Chọn ảnh' }}
        <input type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" class="sr-only" data-input>
    </label>

    @if ($hint && ! $errors->has($name))
        <p class="mt-2 text-xs text-fg-muted">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-2 text-xs text-vermilion">{{ $message }}</p>
    @enderror
</div>
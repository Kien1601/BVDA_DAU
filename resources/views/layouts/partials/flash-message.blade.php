@php
    // [khung, chấm]: nền trong suốt màu trạng thái để hợp cả chế độ sáng và tối; chữ theo text-fg
    $tones = [
        'success' => ['border-emerald-600/25 bg-emerald-500/[.08]', 'bg-emerald-500'],
        'error'   => ['border-vermilion/30 bg-vermilion/5', 'bg-vermilion'],
    ];
@endphp

@foreach ($tones as $key => [$box, $dot])
    @if (session($key))
        <div class="mb-5 flex items-start gap-3 rounded-md border px-4 py-3 text-sm text-fg {{ $box }}" role="status">
            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full {{ $dot }}"></span>
            {{ session($key) }}
        </div>
    @endif
@endforeach
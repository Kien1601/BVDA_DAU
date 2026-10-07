@php
    $tones = [
        'success' => 'border-emerald-600/25 bg-emerald-50 text-emerald-900',
        'error'   => 'border-vermilion/30 bg-vermilion/5 text-vermilion',
    ];
@endphp

@foreach ($tones as $key => $classes)
    @if (session($key))
        <div class="mb-5 flex items-start gap-3 rounded-md border px-4 py-3 text-sm {{ $classes }}" role="status">
            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-current"></span>
            {{ session($key) }}
        </div>
    @endif
@endforeach
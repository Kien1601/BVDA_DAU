{{-- C10 - còn bao nhiêu xe sẵn sàng theo loại. Bấm vào để lọc theo loại đó. --}}
<div class="mb-5 mt-8 flex flex-wrap gap-2">
    @foreach (\App\Models\Vehicle::TYPES as $type)
        @php $left = (int) ($stock[$type] ?? 0); @endphp
        <a href="{{ route('vehicles.index', array_merge(request()->query(), ['type' => $type, 'page' => null])) }}"
           class="rounded-md border px-3 py-2 text-xs transition-colors
                  {{ $filters['type'] === $type ? 'border-ember text-fg' : 'border-edge-soft text-fg-soft hover:border-ember' }}">
            <span class="font-medium">{{ $type }}</span>
            <span class="ml-2 {{ $left ? 'text-fg-muted' : 'text-vermilion' }}">{{ $left ? "Còn {$left} xe" : 'Hết xe' }}</span>
        </a>
    @endforeach
</div>
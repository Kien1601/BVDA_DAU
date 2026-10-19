@extends('layouts.admin')

@section('title', 'Thông báo')

@section('content')
    <x-page-header title="Thông báo" />

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex gap-2">
            <a href="{{ route('admin.notifications.index') }}"
               class="rounded-full px-4 py-1.5 text-sm {{ $onlyUnread ? 'text-fg-soft hover:text-fg' : 'bg-solid text-on-solid' }}">Tất cả</a>
            <a href="{{ route('admin.notifications.index', ['filter' => 'unread']) }}"
               class="rounded-full px-4 py-1.5 text-sm {{ $onlyUnread ? 'bg-solid text-on-solid' : 'text-fg-soft hover:text-fg' }}">Chưa đọc {{ $unreadCount }}</a>
        </div>

        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                @csrf
                <button type="submit" class="text-sm text-fg-soft hover:text-vermilion">Đánh dấu đã đọc hết</button>
            </form>
        @endif
    </div>

    <div class="divide-y divide-edge-soft rounded-lg border border-edge-soft bg-card">
        @forelse ($notifications as $n)
            @php($dot = ['danger' => 'bg-vermilion', 'warning' => 'bg-ember'][$n->data['tone'] ?? ''] ?? 'bg-fg-soft')
            <a href="{{ route('admin.notifications.open', $n->id) }}" class="flex gap-3 px-4 py-3 hover:bg-card-2">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $dot }}"></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm {{ $n->read_at ? 'text-fg-soft' : 'font-semibold text-fg' }}">{{ $n->data['title'] ?? '' }}</span>
                    <span class="block text-sm text-fg-soft">{{ $n->data['message'] ?? '' }}</span>
                </span>
                <span class="shrink-0 text-xs text-fg-muted" title="{{ $n->created_at->format('H:i d/m/Y') }}">{{ $n->created_at->diffForHumans() }}</span>
            </a>
        @empty
            <p class="px-4 py-10 text-center text-sm text-fg-soft">
                {{ $onlyUnread ? 'Không còn thông báo chưa đọc.' : 'Chưa có thông báo nào.' }}
            </p>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
@endsection
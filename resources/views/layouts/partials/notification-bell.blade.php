@php($feed = \App\Http\Controllers\Admin\NotificationController::payload(auth()->user()))

<div x-data="notificationBell(@js($feed), @js(route('admin.notifications.feed')))"
     @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
    <button type="button" @click="toggle()" aria-label="Thông báo" class="relative rounded p-2 hover:text-vermilion">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5" />
        </svg>
        <span x-show="unread > 0" x-cloak x-text="unread > 9 ? '9+' : unread"
              class="absolute -right-0.5 -top-0.5 min-w-[1.1rem] rounded-full bg-vermilion px-1 text-center text-[10px] font-semibold leading-[1.1rem] text-white"></span>
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.right
         class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-lg border border-edge-soft bg-card text-fg shadow-lg">
        <div class="flex items-center justify-between border-b border-edge-soft px-4 py-3">
            <span class="text-sm font-semibold">Thông báo</span>
            <form method="POST" action="{{ route('admin.notifications.read-all') }}" x-show="unread > 0">
                @csrf
                <button type="submit" class="text-xs text-fg-soft hover:text-vermilion">Đánh dấu đã đọc hết</button>
            </form>
        </div>

        <p x-show="items.length === 0" class="px-4 py-6 text-center text-sm text-fg-soft">Chưa có thông báo nào.</p>

        <ul class="max-h-96 divide-y divide-edge-soft overflow-y-auto">
            <template x-for="item in items" :key="item.id">
                <li>
                    <a :href="item.url" class="flex gap-3 px-4 py-3 hover:bg-card-2">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                              :class="{
                                  'bg-vermilion': item.tone === 'danger',
                                  'bg-ember': item.tone === 'warning',
                                  'bg-fg-soft': ! ['danger', 'warning'].includes(item.tone),
                              }"></span>
                        <span class="min-w-0">
                            <span class="block text-sm" :class="item.unread ? 'font-semibold text-fg' : 'text-fg-soft'" x-text="item.title"></span>
                            <span class="block text-sm text-fg-soft" x-text="item.message"></span>
                            <span class="mt-0.5 block text-xs text-fg-muted" x-text="item.time"></span>
                        </span>
                    </a>
                </li>
            </template>
        </ul>
    </div>
</div>
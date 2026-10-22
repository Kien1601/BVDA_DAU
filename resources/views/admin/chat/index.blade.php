@extends('layouts.admin')

@section('content')
<div x-data="chatInbox({
        conversations: @js($conversations),
        listUrl: @js(route('admin.chat.index')),
        messagesUrl: @js(route('admin.chat.messages', '__ID__')),
        storeUrl: @js(route('admin.chat.store', '__ID__')),
        translateUrl: @js(route('admin.chat.translate')),
        csrf: @js(csrf_token()),
    })"
     class="flex h-[calc(100vh-8rem)] overflow-hidden rounded-2xl border border-edge bg-card text-fg">

    {{-- Danh sách hội thoại (điện thoại: ẩn khi đang mở 1 hội thoại) --}}
    <aside class="w-full shrink-0 flex-col border-r border-edge md:flex md:w-80"
           :class="activeId ? 'hidden' : 'flex'">
        <div class="flex items-center gap-2 border-b border-edge px-5 py-4 text-xs font-semibold uppercase tracking-label">
            <span class="h-1.5 w-1.5 rounded-full bg-vermilion"></span>
            Tin nhắn
        </div>
        <div class="flex-1 overflow-y-auto">
            <p x-show="! conversations.length" class="p-5 text-sm text-fg-muted">Chưa có khách nào nhắn tin.</p>

            <template x-for="c in conversations" :key="c.id">
                <button type="button" @click="select(c.id)"
                        class="flex w-full items-start gap-3 border-b border-edge-soft px-5 py-3.5 text-left transition hover:bg-card-2"
                        :class="c.id === activeId ? 'bg-card-2' : ''">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-sm font-medium" x-text="c.customer_name"></span>
                            <span x-show="c.customer_lang && c.customer_lang !== 'vi'"
                                  x-text="(c.customer_lang || '').toUpperCase()"
                                  class="rounded-full border border-edge px-1.5 text-[10px] font-semibold tracking-label text-fg-soft"></span>
                        </div>
                        <p class="mt-0.5 truncate text-sm"
                           :class="c.unread ? 'font-semibold text-fg' : 'text-fg-muted'"
                           x-text="c.last_body"></p>
                    </div>
                    <div class="flex flex-col items-end gap-1.5">
                        <span class="text-[11px] text-fg-muted" x-text="when(c.last_at)"></span>
                        <span x-show="c.unread" class="h-2 w-2 rounded-full bg-vermilion"></span>
                    </div>
                </button>
            </template>
        </div>
    </aside>

    {{-- Khung chat --}}
    <section class="min-w-0 flex-1 flex-col md:flex" :class="activeId ? 'flex' : 'hidden'">
        <div x-show="! activeId" class="flex flex-1 items-center justify-center bg-page text-sm text-fg-muted">
            Chọn một hội thoại để trả lời.
        </div>

        <div x-show="activeId" class="flex min-h-0 flex-1 flex-col">
            <div class="flex items-center gap-3 border-b border-edge px-5 py-4">
                <button type="button" @click="activeId = null" aria-label="Quay lại"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-lg hover:bg-card-2 md:hidden">&larr;</button>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold" x-text="active?.customer_name"></p>
                    <p class="text-[11px] uppercase tracking-label text-fg-muted"
                       x-text="customerLang ? 'Ngôn ngữ: ' + customerLang.toUpperCase() : ''"></p>
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-[11px] uppercase tracking-label text-fg-soft">
                    <span>Dịch sang tiếng Việt</span>
                    <input type="checkbox" class="peer sr-only" :checked="translateOn"
                           @change="setTranslate($event.target.checked)">
                    <span class="relative h-5 w-9 rounded-full bg-card-2 ring-1 ring-edge transition peer-checked:bg-vermilion
                                 after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition
                                 peer-checked:after:translate-x-4"></span>
                </label>
            </div>

            <div x-ref="list" class="flex-1 space-y-3 overflow-y-auto bg-page px-5 py-5">
                <template x-for="m in messages" :key="m.id">
                    <div class="flex" :class="m.sender_type === 'customer' ? 'justify-start' : 'justify-end'">
                        <div class="max-w-[75%]">
                            <p x-show="m.sender_type === 'system'"
                               class="mb-1 px-1 text-right text-[10px] uppercase tracking-label text-fg-muted">Tin tự động</p>
                            <div class="whitespace-pre-line break-words rounded-2xl px-4 py-2.5 text-sm"
                                 :class="{
                                     'rounded-bl-md bg-card-2 text-fg': m.sender_type === 'customer',
                                     'rounded-br-md bg-solid text-on-solid': m.sender_type === 'staff',
                                     'rounded-br-md border border-edge bg-card italic text-fg-soft': m.sender_type === 'system',
                                 }"
                                 x-text="text(m)"></div>
                            <div class="mt-1 flex gap-2 px-1 text-[11px] text-fg-muted"
                                 :class="m.sender_type === 'customer' ? '' : 'justify-end'">
                                <span x-text="when(m.created_at)"></span>
                                <button type="button" x-show="isTranslated(m)" class="underline hover:text-fg"
                                        @click="originals[m.id] = ! originals[m.id]"
                                        x-text="originals[m.id] ? 'Xem bản dịch' : 'Xem bản gốc'"></button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <form @submit.prevent="send()" class="flex items-end gap-2 border-t border-edge p-3">
                <textarea x-model="input" rows="2" maxlength="1000"
                          @keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); send(); }"
                          placeholder="Nhập câu trả lời (Enter để gửi, Shift+Enter để xuống dòng)"
                          class="flex-1 resize-none rounded-2xl border-edge bg-page text-sm text-fg placeholder:text-fg-muted focus:border-fg focus:ring-0"></textarea>
                <button type="submit" :disabled="sending || ! input.trim()"
                        class="h-10 rounded-full bg-solid px-5 text-xs font-semibold uppercase tracking-label text-on-solid transition disabled:opacity-40"
                        x-text="sending ? '...' : 'Gửi'"></button>
            </form>
            <p x-show="error" x-text="error" class="px-4 pb-2 text-xs text-vermilion"></p>
        </div>
    </section>
</div>
@endsection
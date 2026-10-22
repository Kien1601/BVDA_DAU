{{-- Khung chat với nhân viên: nút tròn cố định góc phải dưới, cuộn trang vẫn hiện --}}
@can('chat.use')
<div x-data="chatWidget({
        indexUrl: @js(route('chat.index')),
        storeUrl: @js(route('chat.store')),
        readUrl: @js(route('chat.read')),
        translateUrl: @js(route('chat.translate')),
        csrf: @js(csrf_token()),
    })"
     class="fixed bottom-5 right-5 z-[1100]">

    {{-- Nút mở chat --}}
    <button type="button" @click="toggle()" x-show="! open" aria-label="Chat"
            class="relative flex h-14 w-14 items-center justify-center rounded-full bg-solid text-on-solid shadow-lg transition duration-300 ease-soft hover:scale-105">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M8 10h8M8 14h5M5 20l3-3h11a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2v3z"/>
        </svg>
        <span x-show="unread > 0" x-text="unread > 9 ? '9+' : unread"
              class="absolute -right-1 -top-1 min-w-5 rounded-full bg-vermilion px-1.5 text-xs font-semibold leading-5 text-white"></span>
    </button>

    {{-- Khung chat: điện thoại chiếm toàn màn hình, máy tính là hộp nổi --}}
    <div x-show="open" x-cloak x-transition
         class="fixed inset-0 flex flex-col bg-card text-fg shadow-2xl sm:absolute sm:inset-auto sm:bottom-0 sm:right-0 sm:h-[32rem] sm:w-96 sm:rounded-2xl sm:border sm:border-edge">

        <div class="flex items-center gap-3 border-b border-edge px-5 py-4">
            <div class="flex-1">
                <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-label">
                    <span class="h-1.5 w-1.5 rounded-full bg-vermilion"></span>
                    Hỗ trợ khách hàng
                </p>
                <p class="mt-0.5 text-[11px] uppercase tracking-label text-fg-muted">Rental GPS</p>
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-[11px] uppercase tracking-label text-fg-soft">
                <span>Dịch</span>
                <input type="checkbox" class="peer sr-only" :checked="translateOn"
                       @change="setTranslate($event.target.checked)">
                <span class="relative h-5 w-9 rounded-full bg-card-2 ring-1 ring-edge transition peer-checked:bg-vermilion
                             after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition
                             peer-checked:after:translate-x-4"></span>
            </label>
            <button type="button" @click="toggle()" aria-label="Đóng"
                    class="flex h-8 w-8 items-center justify-center rounded-full text-xl leading-none text-fg-soft hover:bg-card-2">&times;</button>
        </div>

        <div x-ref="list" class="flex-1 space-y-3 overflow-y-auto bg-page px-4 py-5">
            <p x-show="loaded && ! messages.length" class="text-center text-sm text-fg-muted">
                Bạn cần hỗ trợ gì? Hãy nhắn cho chúng tôi.<br>How can we help you?
            </p>

            <template x-for="m in messages" :key="m.id">
                <div class="flex" :class="m.sender_type === 'customer' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[80%]">
                        <div class="whitespace-pre-line break-words rounded-2xl px-4 py-2.5 text-sm"
                             :class="{
                                 'rounded-br-md bg-solid text-on-solid': m.sender_type === 'customer',
                                 'rounded-bl-md bg-card-2 text-fg': m.sender_type === 'staff',
                                 'rounded-bl-md border border-edge bg-card italic text-fg-soft': m.sender_type === 'system',
                             }"
                             x-text="text(m)"></div>
                        <div class="mt-1 flex gap-2 px-1 text-[11px] text-fg-muted"
                             :class="m.sender_type === 'customer' ? 'justify-end' : ''">
                            <span x-text="time(m)"></span>
                            <button type="button" x-show="isTranslated(m)" class="underline hover:text-fg"
                                    @click="originals[m.id] = ! originals[m.id]"
                                    x-text="originals[m.id] ? 'Bản dịch / Translation' : 'Bản gốc / Original'"></button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <form @submit.prevent="send()" class="flex items-end gap-2 border-t border-edge p-3">
            <textarea x-model="input" rows="1" maxlength="1000"
                      @keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); send(); }"
                      placeholder="Nhập tin nhắn... / Type a message..."
                      class="flex-1 resize-none rounded-2xl border-edge bg-page text-sm text-fg placeholder:text-fg-muted focus:border-fg focus:ring-0"></textarea>
            <button type="submit" :disabled="sending || ! input.trim()"
                    class="h-10 rounded-full bg-solid px-5 text-xs font-semibold uppercase tracking-label text-on-solid transition disabled:opacity-40"
                    x-text="sending ? '...' : 'Gửi'"></button>
        </form>
        <p x-show="error" x-text="error" class="px-4 pb-2 text-xs text-vermilion"></p>
    </div>
</div>
@endcan
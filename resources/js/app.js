import './shared/config/echo';
import { initThemeToggle } from './shared/theme/theme';

import Alpine from 'alpinejs';
import chatWidget from './shared/chat/chatWidget';
import chatInbox from './shared/chat/chatInbox';

window.Alpine = Alpine;
Alpine.data('notificationBell', (unread, feedUrl, userId) => ({
    open: false,
    unread,
    // Danh sách tải khi mở chuông (toggle gọi refresh), trang không nhúng sẵn nội dung thông báo
    items: [],
    loaded: false,
    toast: null,
    toastTimer: null,

    init() {
        window.Echo?.private(`App.Models.User.${userId}`)
            .notification((n) => {
                this.refresh();
                this.showToast(n);
            });

        // Dự phòng khi Reverb không chạy: 60 giây hỏi lại một lần, chỉ khi tab đang mở
        setInterval(() => { if (! document.hidden) this.refresh(); }, 60000);
    },

    toggle() {
        this.open = ! this.open;
        if (this.open) this.refresh();
    },

    showToast(n) {
        this.toast = n;
        clearTimeout(this.toastTimer);
        this.toastTimer = setTimeout(() => { this.toast = null; }, 6000);
    },

    async refresh() {
        const res = await window.fetch(feedUrl, { headers: { Accept: 'application/json' } });
        if (res.ok) Object.assign(this, await res.json(), { loaded: true });
    },
}));

Alpine.data('chatWidget', chatWidget);
Alpine.data('chatInbox', chatInbox);
Alpine.start();

initThemeToggle();

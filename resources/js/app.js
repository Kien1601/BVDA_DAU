import './shared/config/echo';
import { initThemeToggle } from './shared/theme/theme';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.data('notificationBell', (initial, feedUrl) => ({
    open: false,
    unread: initial.unread,
    items: initial.items,

    toggle() {
        this.open = ! this.open;
        if (this.open) this.refresh();
    },

    async refresh() {
        const res = await window.fetch(feedUrl, { headers: { Accept: 'application/json' } });
        if (res.ok) Object.assign(this, await res.json());
    },
}));
Alpine.start();

initThemeToggle();

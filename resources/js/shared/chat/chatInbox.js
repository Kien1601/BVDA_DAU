// Trang Tin nhắn của nhân viên: danh sách hội thoại + khung chat, nhận realtime qua kênh chat.staff
export default (config) => ({
    conversations: config.conversations,
    activeId: null,
    customerLang: null,
    messages: [],
    input: '',
    sending: false,
    error: null,
    translateOn: true,
    translations: {},
    originals: {},

    init() {
        try { this.translateOn = localStorage.getItem('chatTranslateStaff') !== '0'; } catch {}

        window.Echo?.private('chat.staff').listen('.message.sent', (m) => this.incoming(m));

        // Dự phòng khi Reverb không chạy: 60 giây tải lại danh sách
        setInterval(() => { if (! document.hidden) this.reloadList(); }, 60000);
    },

    get active() {
        return this.conversations.find((c) => c.id === this.activeId);
    },

    async select(id) {
        this.activeId = id;
        this.messages = [];
        this.error = null;
        const res = await this.request(this.url(config.messagesUrl, id));
        if (! res || this.activeId !== id) return;
        this.messages = res.messages;
        this.customerLang = res.customer_lang;
        if (this.active) this.active.unread = false;
        this.afterChange();
    },

    async incoming(m) {
        let c = this.conversations.find((x) => x.id === m.conversation_id);
        if (! c) {
            await this.reloadList();
            c = this.conversations.find((x) => x.id === m.conversation_id);
            if (! c) return;
        }

        c.last_body = m.body;
        if (m.sender_type !== 'system') c.last_at = m.created_at;
        this.conversations = [c, ...this.conversations.filter((x) => x.id !== c.id)];

        if (m.conversation_id !== this.activeId) {
            if (m.sender_type === 'customer') c.unread = true;
            return;
        }

        if (this.messages.some((x) => x.id === m.id)) return;
        this.messages.push(m);
        // Đang mở đúng hội thoại: gọi lại để server ghi nhận đã đọc
        if (m.sender_type === 'customer') this.request(this.url(config.messagesUrl, this.activeId));
        this.afterChange();
    },

    async reloadList() {
        const res = await this.request(config.listUrl);
        if (! res) return;
        this.conversations = res.map((c) => (c.id === this.activeId ? { ...c, unread: false } : c));
    },

    async send() {
        const body = this.input.trim();
        if (! body || this.sending || ! this.activeId) return;
        this.sending = true;
        this.error = null;
        const m = await this.request(this.url(config.storeUrl, this.activeId), { body });
        this.sending = false;
        if (! m) {
            this.error = 'Gửi không được, vui lòng thử lại.';
            return;
        }
        this.input = '';
        this.incoming(m);
    },

    setTranslate(on) {
        this.translateOn = on;
        try { localStorage.setItem('chatTranslateStaff', on ? '1' : '0'); } catch {}
        if (on) this.translateVisible();
    },

    // Dịch tin của khách (và tin tự động) không phải tiếng Việt
    async translateVisible() {
        const ids = this.messages
            .filter((m) => m.sender_type !== 'staff' && m.lang && m.lang !== 'vi' && ! (m.id in this.translations))
            .slice(-50)
            .map((m) => m.id);
        if (! ids.length) return;
        ids.forEach((id) => { this.translations[id] = null; });
        const res = await this.request(config.translateUrl, { ids });
        if (res) Object.assign(this.translations, res);
    },

    text(m) {
        if (! this.translateOn || this.originals[m.id]) return m.body;
        return this.translations[m.id] ?? m.body;
    },

    isTranslated(m) {
        return this.translateOn && !! this.translations[m.id] && this.translations[m.id] !== m.body;
    },

    // Hôm nay hiện giờ, ngày khác hiện ngày/tháng
    when(iso) {
        if (! iso) return '';
        const d = new Date(iso);
        return d.toDateString() === new Date().toDateString()
            ? d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
            : d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' });
    },

    afterChange() {
        this.$nextTick(() => {
            if (this.$refs.list) this.$refs.list.scrollTop = this.$refs.list.scrollHeight;
        });
        if (this.translateOn) this.translateVisible();
    },

    url(template, id) {
        return template.replace('__ID__', id);
    },

    async request(url, body = null) {
        try {
            const res = await window.fetch(url, {
                method: body ? 'POST' : 'GET',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                },
                body: body ? JSON.stringify(body) : null,
            });
            return res.ok ? await res.json() : null;
        } catch {
            return null;
        }
    },
});
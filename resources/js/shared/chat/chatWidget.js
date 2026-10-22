// Khung chat của khách: tải lịch sử, gửi tin, nhận realtime qua Reverb, bật/tắt dịch
export default (config) => ({
    open: false,
    loaded: false,
    conversationId: null,
    messages: [],
    unread: 0,
    input: '',
    sending: false,
    error: null,
    translateOn: false,
    translations: {},
    originals: {},
    lang: (navigator.language || 'vi').slice(0, 2).toLowerCase(),

    async init() {
        try { this.translateOn = localStorage.getItem('chatTranslate') === '1'; } catch {}

        await this.load();
        window.Echo?.private(`chat.${this.conversationId}`)
            .listen('.message.sent', (m) => this.receive(m));

        // Dự phòng khi Reverb không chạy: 30 giây tải lại khi khung đang mở
        setInterval(() => { if (this.open && ! document.hidden) this.load(); }, 30000);
    },

    async load() {
        const res = await this.request(config.indexUrl);
        if (! res) return;
        this.conversationId = res.conversation_id;
        this.messages = res.messages;
        this.unread = this.open ? 0 : res.unread;
        this.loaded = true;
        this.afterChange();
    },

    receive(m) {
        if (this.messages.some((x) => x.id === m.id)) return;
        this.messages.push(m);
        if (m.sender_type !== 'customer') {
            if (this.open && ! document.hidden) this.markRead();
            else this.unread++;
        }
        this.afterChange();
    },

    toggle() {
        this.open = ! this.open;
        if (this.open) {
            this.unread = 0;
            this.markRead();
            this.afterChange();
        }
    },

    async send() {
        const body = this.input.trim();
        if (! body || this.sending) return;
        this.sending = true;
        this.error = null;
        const m = await this.request(config.storeUrl, { body });
        this.sending = false;
        if (! m) {
            this.error = 'Gửi không được, vui lòng thử lại. / Could not send, please retry.';
            return;
        }
        this.input = '';
        this.receive(m);
    },

    markRead() {
        this.request(config.readUrl, {});
    },

    setTranslate(on) {
        this.translateOn = on;
        try { localStorage.setItem('chatTranslate', on ? '1' : '0'); } catch {}
        if (on) this.translateVisible();
    },

    // Chỉ dịch tin của bên kia, khác ngôn ngữ của khách, chưa dịch lần nào
    async translateVisible() {
        const ids = this.messages
            .filter((m) => m.sender_type !== 'customer' && m.lang && m.lang !== this.lang && ! (m.id in this.translations))
            .slice(-50)
            .map((m) => m.id);
        if (! ids.length) return;
        ids.forEach((id) => { this.translations[id] = null; });
        const res = await this.request(config.translateUrl, { ids, lang: this.lang });
        if (res) Object.assign(this.translations, res);
    },

    text(m) {
        if (! this.translateOn || this.originals[m.id]) return m.body;
        return this.translations[m.id] ?? m.body;
    },

    isTranslated(m) {
        return this.translateOn && !! this.translations[m.id] && this.translations[m.id] !== m.body;
    },

    time(m) {
        return new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },

    afterChange() {
        this.$nextTick(() => {
            if (this.$refs.list) this.$refs.list.scrollTop = this.$refs.list.scrollHeight;
        });
        if (this.translateOn) this.translateVisible();
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
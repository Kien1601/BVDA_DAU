/**
 * Nút đổi chế độ sáng/tối ([data-theme-toggle], component <x-theme-toggle>).
 * Chế độ ban đầu do layouts/partials/theme-boot.blade.php đặt trước khi trang vẽ.
 * Mỗi lần đổi phát sự kiện window 'theme:change' (detail.theme) để những phần tự vẽ màu (như bản đồ) đổi theo.
 */
const KEY = 'theme';
const THEMES = ['light', 'dark'];

const root = document.documentElement;

function current() {
    return root.dataset.theme === 'light' ? 'light' : 'dark';
}

function syncButtons(theme) {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', String(theme === 'dark'));
        button.title = theme === 'dark' ? 'Chuyển sang chế độ sáng' : 'Chuyển sang chế độ tối';
    });
}

function apply(theme) {
    root.dataset.theme = theme;
    syncButtons(theme);
    window.dispatchEvent(new CustomEvent('theme:change', { detail: { theme } }));
}

export function initThemeToggle() {
    syncButtons(current());

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-theme-toggle]')) return;

        const next = current() === 'dark' ? 'light' : 'dark';
        try {
            localStorage.setItem(KEY, next);
        } catch {
            // localStorage bị chặn: vẫn đổi cho trang hiện tại, chỉ không nhớ được
        }
        apply(next);
    });

    // đồng bộ giữa các tab: tab khác đổi chế độ thì tab này đổi theo
    window.addEventListener('storage', (event) => {
        if (event.key !== KEY) return;
        const theme = THEMES.includes(event.newValue) ? event.newValue : (root.dataset.areaDefault || current());
        if (theme !== current()) apply(theme);
    });
}

import { fetchQuote } from './booking.service';
import { debounce } from '../../../js/shared/utils/debounce';
import { formatCurrency } from '../../../js/shared/utils/formatCurrency';

const form = document.querySelector('[data-booking-form]');
if (form) initQuote(form);

document.querySelectorAll('[data-countdown]').forEach(initCountdown);

/* ---------------------------------------------------------------- báo giá trực tiếp */
function initQuote(form) {
    const FIELDS = ['start_time', 'end_time', 'promotion_code'];
    const out = (key) => form.querySelector(`[data-q="${key}"]`);
    const discountRow = form.querySelector('[data-q-row="discount"]');
    const errorBox = form.querySelector('[data-q-error]');
    const submit = form.querySelector('[data-submit]');
    let sequence = 0;

    const clearLiveErrors = () => form.querySelectorAll('[data-live-error]').forEach((el) => el.remove());

    // Hiện lỗi ngay dưới ô tương ứng; trả về false nếu không có ô đó (lỗi chung như "vehicle")
    const showFieldError = (name, message) => {
        const input = form.elements[name];
        if (!input) return false;
        const p = document.createElement('p');
        p.dataset.liveError = '';
        p.className = 'mt-1 text-xs text-vermilion';
        p.textContent = message;
        input.closest('div').appendChild(p);
        return true;
    };

    const resetSummary = () => {
        ['breakdown', 'subtotal', 'total', 'deposit'].forEach((key) => { out(key).textContent = '—'; });
        discountRow.classList.add('hidden');
    };

    const run = debounce(async () => {
        const id = ++sequence;
        const payload = Object.fromEntries(FIELDS.map((name) => [name, form.elements[name]?.value ?? '']));
        if (!payload.start_time || !payload.end_time) return;

        form.dataset.state = 'loading';
        try {
            const result = await fetchQuote(form.dataset.quoteUrl, payload);
            if (id !== sequence) return; // đã có lần gọi mới hơn, bỏ kết quả cũ

            clearLiveErrors();
            errorBox.classList.add('hidden');

            if (!result.ok) {
                const general = Object.entries(result.errors)
                    .filter(([name, messages]) => !showFieldError(name, messages[0]))
                    .map(([, messages]) => messages[0]);
                if (general.length) {
                    errorBox.textContent = general.join(' ');
                    errorBox.classList.remove('hidden');
                }
                resetSummary();
                submit.disabled = true;
                return;
            }

            const q = result.quote;
            out('breakdown').textContent = q.breakdown;
            out('subtotal').textContent = formatCurrency(q.subtotal);
            out('total').textContent = formatCurrency(q.total);
            out('deposit').textContent = formatCurrency(q.deposit);
            out('discount').textContent = `− ${formatCurrency(q.discount)}`;
            out('promotion').textContent = q.promotion ? `${q.promotion.code} · ${q.promotion.label}` : '';
            discountRow.classList.toggle('hidden', !q.discount);
            submit.disabled = false;
        } catch (error) {
            if (id !== sequence) return;
            errorBox.textContent = error.message;
            errorBox.classList.remove('hidden');
        } finally {
            if (id === sequence) delete form.dataset.state;
        }
    }, 350);

    FIELDS.forEach((name) => form.elements[name]?.addEventListener('input', run));
    run();
}

/* ---------------------------------------------------------------- đếm ngược giữ chỗ */
function initCountdown(el) {
    const end = new Date(el.dataset.countdown).getTime();

    const tick = () => {
        const left = Math.max(0, end - Date.now());
        const minutes = Math.floor(left / 60000);
        const seconds = Math.floor((left % 60000) / 1000);
        el.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

        if (left === 0) {
            clearInterval(timer);
            // tải lại để server hiển thị trạng thái "hết hạn giữ chỗ"
            setTimeout(() => window.location.reload(), 1200);
        }
    };

    const timer = setInterval(tick, 1000);
    tick();
}
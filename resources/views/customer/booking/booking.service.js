/** Gọi API báo giá. Trả về { ok: true, quote } hoặc { ok: false, errors } (lỗi 422). */
export async function fetchQuote(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (response.status === 422) return { ok: false, errors: data.errors || {} };
    if (!response.ok) throw new Error(`Không tính được giá (HTTP ${response.status}). Vui lòng thử lại.`);

    return { ok: true, quote: data };
}
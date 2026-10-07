/**
 * Xem trước ảnh ngay khi chọn file, trước khi lưu form.
 * Dùng cho mọi khối có thuộc tính data-image-upload.
 */
export function initImagePreview(root = document) {
    root.querySelectorAll('[data-image-upload]').forEach((box) => {
        const input = box.querySelector('[data-input]');
        const img = box.querySelector('[data-preview]');
        const placeholder = box.querySelector('[data-placeholder]');
        let objectUrl = null;

        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;

            if (objectUrl) URL.revokeObjectURL(objectUrl); // trả bộ nhớ của ảnh chọn trước đó
            objectUrl = URL.createObjectURL(file);

            img.src = objectUrl;
            img.classList.remove('hidden');
            placeholder.classList.add('hidden');
        });
    });
}
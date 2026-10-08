/**
 * Thẻ có data-tilt="độ" nghiêng theo chuột (mặc định 6 độ), kèm vệt sáng .tilt-glare đi theo con trỏ.
 * Chỉ chạy với chuột; màn hình cảm ứng và người dùng giảm chuyển động thì bỏ qua.
 */
export function initTilt(root = document) {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (!matchMedia('(hover: hover) and (pointer: fine)').matches) return;

    root.querySelectorAll('[data-tilt]').forEach((el) => {
        const max = Number(el.dataset.tilt) || 6;
        let frame = 0;

        el.addEventListener('pointermove', (event) => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => {
                const box = el.getBoundingClientRect();
                const x = (event.clientX - box.left) / box.width;
                const y = (event.clientY - box.top) / box.height;
                el.style.setProperty('--tilt-x', `${((0.5 - y) * max).toFixed(2)}deg`);
                el.style.setProperty('--tilt-y', `${((x - 0.5) * max).toFixed(2)}deg`);
                el.style.setProperty('--glare-x', `${(x * 100).toFixed(1)}%`);
                el.style.setProperty('--glare-y', `${(y * 100).toFixed(1)}%`);
            });
        });

        el.addEventListener('pointerleave', () => {
            cancelAnimationFrame(frame);
            el.style.setProperty('--tilt-x', '0deg');
            el.style.setProperty('--tilt-y', '0deg');
        });
    });
}

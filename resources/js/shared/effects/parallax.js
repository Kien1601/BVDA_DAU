/**
 * Các lớp có data-parallax="tốc độ" trượt lệch nhau khi cuộn trang.
 * Tốc độ dương: lớp trôi chậm hơn trang; âm: trôi ngược lại.
 */
export function initParallax(root = document) {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const layers = [...root.querySelectorAll('[data-parallax]')];
    if (!layers.length) return;

    let ticking = false;
    const update = () => {
        ticking = false;
        const centre = window.innerHeight / 2;
        layers.forEach((el) => {
            const box = el.parentElement.getBoundingClientRect();
            const offset = (box.top + box.height / 2 - centre) * (Number(el.dataset.parallax) || 0.1);
            el.style.transform = `translate3d(0, ${offset.toFixed(1)}px, 0)`;
        });
    };

    window.addEventListener('scroll', () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    }, { passive: true });

    update();
}
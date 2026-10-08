import './effects.css';

/** Phần tử có data-reveal hiện dần khi cuộn tới. Độ trễ: style="--reveal-delay: 120ms" */
export function initReveal(root = document) {
    const items = root.querySelectorAll('[data-reveal]');
    if (!items.length) return;

    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-revealed'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            observer.unobserve(entry.target);
            entry.target.classList.add('is-revealed');
        });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.05 });

    items.forEach((el) => observer.observe(el));
}
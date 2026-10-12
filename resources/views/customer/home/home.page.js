import { createCityScene } from '../../../js/shared/scene/cityScene';

const host = document.getElementById('city-scene');
const hero = document.getElementById('hero');
const modeFor = (theme) => (theme === 'light' ? 'day' : 'night');

if (host && hero) {
    const city = createCityScene(host, {
        quality: host.dataset.quality || 'high',
        mode: modeFor(document.documentElement.dataset.theme),
    });

    const onScroll = () => {
        city.setProgress(Math.min(1, Math.max(0, window.scrollY / hero.offsetHeight)));
    };
    // bấm nút đổi chế độ: thành phố chuyển dần ngày ⇄ đêm
    const onTheme = (event) => city.setMode(modeFor(event.detail.theme));

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('theme:change', onTheme);
    onScroll();

    window.addEventListener('pagehide', () => {
        window.removeEventListener('scroll', onScroll);
        window.removeEventListener('theme:change', onTheme);
        city.dispose();
    }, { once: true });
}
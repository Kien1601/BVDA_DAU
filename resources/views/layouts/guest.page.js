import { createCityScene } from '../../js/shared/scene/cityScene';

const host = document.getElementById('city-scene');
const modeFor = (theme) => (theme === 'light' ? 'day' : 'night');

if (host) {
    const city = createCityScene(host, {
        quality: host.dataset.quality || 'low',
        mode: modeFor(document.documentElement.dataset.theme),
    });

    const onTheme = (event) => city.setMode(modeFor(event.detail.theme));
    window.addEventListener('theme:change', onTheme);

    window.addEventListener('pagehide', () => {
        window.removeEventListener('theme:change', onTheme);
        city.dispose();
    }, { once: true });
}
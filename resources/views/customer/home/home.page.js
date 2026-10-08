import { createCityScene } from '../../../js/shared/scene/cityScene';

const host = document.getElementById('city-scene');

if (host) {
    const city = createCityScene(host, { quality: host.dataset.quality || 'high' });
    // rời trang: trả lại bộ nhớ đồ họa
    window.addEventListener('pagehide', () => city.dispose(), { once: true });
}

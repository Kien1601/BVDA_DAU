import { renderStoreMap } from '../../../js/shared/map/storeMap';

const element = document.getElementById('store-map');
const result = element ? renderStoreMap(element, { zoom: 14 }) : null;

if (result) {
    document.querySelectorAll('[data-store-index]').forEach((button) => {
        button.addEventListener('click', () => {
            const marker = result.markers[Number(button.dataset.storeIndex)];
            if (!marker) return;
            result.map.setView(marker.getLatLng(), 16);
            marker.openPopup();
            if (window.innerWidth < 1024) element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });
}
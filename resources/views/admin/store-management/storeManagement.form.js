import L from '../../../js/shared/config/leaflet';
import { createMap } from '../../../js/shared/map/createMap';

const mapEl = document.getElementById('store-location-map');
const latInput = document.getElementById('latitude');
const lngInput = document.getElementById('longitude');

if (mapEl) {
    const hasValue = latInput.value !== '' && lngInput.value !== '';

    // Sửa cửa hàng: mở đúng vị trí đã lưu. Thêm mới: mở ở trung tâm TP.HCM
    const start = hasValue
        ? [Number(latInput.value), Number(lngInput.value)]
        : [Number(mapEl.dataset.defaultLat), Number(mapEl.dataset.defaultLng)];

    const map = createMap(mapEl, { center: start, zoom: hasValue ? 17 : 13 });
    let marker = null;

    function setPosition(latlng) {
        latInput.value = latlng.lat.toFixed(7);
        lngInput.value = latlng.lng.toFixed(7);

        if (!marker) {
            marker = L.marker(latlng, { draggable: true }).addTo(map);
            marker.on('dragend', () => setPosition(marker.getLatLng()));
        } else {
            marker.setLatLng(latlng);
        }
    }

    if (hasValue) {
        setPosition(L.latLng(start));
    }

    map.on('click', (event) => setPosition(event.latlng));
}
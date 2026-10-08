import L from '../config/leaflet';
import { createMap } from './createMap';
import './storeMap.css';

/**
 * Bản đồ cửa hàng phía khách (B1.4). Đọc danh sách cửa hàng từ data-stores (JSON).
 * Chỉ có tọa độ cửa hàng; vị trí GPS của xe không bao giờ được gửi xuống trang khách.
 */
export function renderStoreMap(element, { zoom = 15 } = {}) {
    let stores = [];
    try {
        stores = JSON.parse(element.dataset.stores || '[]');
    } catch {
        stores = [];
    }
    if (!stores.length) return null;

    const map = createMap(element, { center: [stores[0].lat, stores[0].lng], zoom, provider: 'esriDark' });

    const icon = L.divIcon({
        className: 'store-pin-wrap',
        html: '<span class="store-pin"></span>',
        iconSize: [18, 18],
        iconAnchor: [9, 9],
        popupAnchor: [0, -10],
    });

    const markers = stores.map((store) => L.marker([store.lat, store.lng], { icon, title: store.name })
        .addTo(map)
        .bindPopup(() => popupContent(store), { className: 'store-popup' }));

    if (stores.length > 1) {
        map.fitBounds(L.latLngBounds(stores.map((s) => [s.lat, s.lng])), { padding: [50, 50], maxZoom: 15 });
    } else {
        markers[0].openPopup();
    }

    return { map, markers };
}

/* Dựng bằng textContent để chống chèn mã từ dữ liệu */
function popupContent(store) {
    const box = document.createElement('div');

    const title = document.createElement('strong');
    title.textContent = store.name;
    const address = document.createElement('div');
    address.textContent = store.address;
    box.append(title, address);

    if (store.vehicles != null) {
        const meta = document.createElement('div');
        meta.className = 'store-popup-meta';
        meta.textContent = `${store.vehicles} xe sẵn sàng`;
        box.append(meta);
    }

    if (store.url) {
        const link = document.createElement('a');
        link.href = store.url;
        link.textContent = 'Xem xe tại cửa hàng →';
        box.append(link);
    }

    return box;
}
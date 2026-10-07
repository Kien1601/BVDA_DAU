import L from '../config/leaflet';

/**
 * Các nguồn nền bản đồ không cần API key.
 * Chọn bằng biến VITE_MAP_PROVIDER trong .env (cần build lại sau khi đổi).
 * Lý do có nhiều lựa chọn: một số mạng chặn tile.openstreetmap.org.
 */
const TILE_PROVIDERS = {
    osm: {
        url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        options: {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        },
    },
    osmHot: {
        url: 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
        options: {
            maxZoom: 19,
            subdomains: 'abc',
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Humanitarian OSM Team',
        },
    },
    esri: {
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
        options: {
            maxZoom: 19,
            attribution: 'Tiles &copy; Esri',
        },
    },
};

const provider = TILE_PROVIDERS[import.meta.env.VITE_MAP_PROVIDER] ?? TILE_PROVIDERS.esri;

/**
 * Tạo bản đồ Leaflet dùng chung cho: giám sát GPS (C9),
 * vị trí cửa hàng (B1.4), chọn tọa độ cửa hàng (C3.2).
 */
export function createMap(element, { center, zoom = 15 } = {}) {
    const map = L.map(element).setView(center, zoom);

    L.tileLayer(provider.url, provider.options).addTo(map);

    // Khung chứa có thể đổi kích thước sau khi bản đồ đã tạo: báo lại để Leaflet tải đủ tile
    new ResizeObserver(() => map.invalidateSize()).observe(element);

    return map;
}
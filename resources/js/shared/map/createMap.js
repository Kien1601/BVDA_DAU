import L from '../config/leaflet';

/**
 * Các nguồn nền bản đồ không cần API key.
 * Mặc định chọn bằng VITE_MAP_PROVIDER; một trang có thể chỉ định riêng qua tham số provider.
 */
const TILE_PROVIDERS = {
    osm: {
        url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        options: { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors' },
    },
    osmHot: {
        url: 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
        options: { maxZoom: 19, subdomains: 'abc', attribution: '&copy; OpenStreetMap contributors, Humanitarian OSM Team' },
    },
    esri: {
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
        options: { maxZoom: 19, attribution: 'Tiles &copy; Esri' },
    },
    /* nền xám đậm cho trang khách (phong cách tối) */
    esriDark: {
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}',
        options: { maxZoom: 16, attribution: 'Tiles &copy; Esri' },
    },
};

/**
 * Tạo bản đồ Leaflet dùng chung: giám sát GPS (C9), chọn tọa độ cửa hàng (C3.2),
 * vị trí cửa hàng phía khách (B1.4).
 */
/* lớp nền đang dùng của từng bản đồ, để setMapProvider() thay được */
const baseLayers = new WeakMap();

export function createMap(element, { center, zoom = 15, provider } = {}) {
    const tiles = TILE_PROVIDERS[provider ?? import.meta.env.VITE_MAP_PROVIDER] ?? TILE_PROVIDERS.esri;
    const map = L.map(element).setView(center, Math.min(zoom, tiles.options.maxZoom));

    baseLayers.set(map, L.tileLayer(tiles.url, tiles.options).addTo(map));

    // Khung chứa có thể đổi kích thước sau khi bản đồ đã tạo: báo lại để Leaflet tải đủ tile
    new ResizeObserver(() => map.invalidateSize()).observe(element);

    return map;
}

/** Đổi lớp nền của bản đồ đã tạo (ví dụ khi đổi chế độ sáng/tối). Tên nền lạ thì bỏ qua. */
export function setMapProvider(map, provider) {
    const tiles = TILE_PROVIDERS[provider];
    if (!tiles) return;

    baseLayers.get(map)?.remove();
    baseLayers.set(map, L.tileLayer(tiles.url, tiles.options).addTo(map));

    // nền tối chỉ có tới mức zoom 16: kéo bản đồ về trong giới hạn của nền mới
    map.setMaxZoom(tiles.options.maxZoom);
    if (map.getZoom() > tiles.options.maxZoom) map.setZoom(tiles.options.maxZoom);
}

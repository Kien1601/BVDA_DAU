import './gpsMonitor.css';
import L from '../../../js/shared/config/leaflet';
import { createMap } from '../../../js/shared/map/createMap';
import { fetchPositions } from './gpsMonitor.service';
import { subscribeVehicleUpdates } from './gpsMonitor.channel';

// Quá 2 phút không có tín hiệu thì hiển thị là "mất tín hiệu" (chỉ hiển thị, chưa phải C10)
const STALE_AFTER_MS = 2 * 60 * 1000;

const SIGNAL = {
    online: { label: 'Đang có tín hiệu', color: '#16a34a' },
    stale: { label: 'Mất tín hiệu', color: '#9ca3af' },
    none: { label: 'Chưa có dữ liệu', color: '#d1d5db' },
};

const CONNECTION_TEXT = {
    initialized: 'Đang kết nối…',
    connecting: 'Đang kết nối…',
    connected: 'Đang nhận dữ liệu real-time',
    unavailable: 'Mất kết nối máy chủ real-time',
    disconnected: 'Mất kết nối máy chủ real-time',
    failed: 'Không kết nối được máy chủ real-time',
    denied: 'Không có quyền nhận dữ liệu vị trí',
};

const root = document.getElementById('gps-monitor');
const listEl = document.getElementById('gps-vehicle-list');
const connectionEl = document.getElementById('gps-connection');

const map = createMap(document.getElementById('gps-map'), {
    center: [Number(root.dataset.lat), Number(root.dataset.lng)],
});

/** vehicle_id -> { data, marker } */
const vehicles = new Map();

// ---------- Tiện ích ----------

function signalState(v) {
    if (v.lat == null || v.lng == null || !v.last_signal_at) return 'none';

    return Date.now() - new Date(v.last_signal_at).getTime() > STALE_AFTER_MS ? 'stale' : 'online';
}

function formatTime(iso) {
    return iso ? new Date(iso).toLocaleString('vi-VN') : '—';
}

// Dựng HTML bằng textContent (không dùng innerHTML) để chống chèn mã độc từ dữ liệu
function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null) node.textContent = text;
    return node;
}

// ---------- C9.3: popup chi tiết ----------

function popupContent(v) {
    const box = el('div', 'gps-popup');
    box.append(
        el('strong', null, v.license_plate),
        el('div', null, v.name ?? ''),
        el('div', null, `Khách đang thuê: ${v.renter ?? '—'}`),
        el('div', null, `Tốc độ: ${v.speed != null ? Number(v.speed).toFixed(1) : '—'} km/h`),
        el('div', null, `Cập nhật: ${formatTime(v.last_signal_at)}`),
    );
    return box;
}

// ---------- Marker ----------

function drawMarker(entry) {
    const v = entry.data;
    if (v.lat == null || v.lng == null) return; // xe chưa từng có tín hiệu: không vẽ

    const style = {
        radius: 9,
        weight: 2,
        color: '#ffffff',
        fillColor: SIGNAL[signalState(v)].color,
        fillOpacity: 1,
    };

    if (!entry.marker) {
        entry.marker = L.circleMarker([v.lat, v.lng], style)
            .addTo(map)
            .bindPopup(() => popupContent(entry.data)); // tính lại nội dung mỗi lần mở
        return;
    }

    entry.marker.setLatLng([v.lat, v.lng]).setStyle(style);

    if (entry.marker.isPopupOpen()) {
        entry.marker.getPopup().update();
    }
}

function upsert(data) {
    const entry = vehicles.get(data.vehicle_id) ?? { data: {}, marker: null };

    // Event real-time chỉ gửi vài trường: gộp vào dữ liệu cũ để không mất tên xe, khách thuê
    entry.data = { ...entry.data, ...data };
    vehicles.set(data.vehicle_id, entry);

    drawMarker(entry);
}

// ---------- Danh sách xe ----------

function renderList() {
    const entries = [...vehicles.values()]
        .sort((a, b) => a.data.license_plate.localeCompare(b.data.license_plate));

    if (!entries.length) {
        listEl.replaceChildren(el('li', 'gps-vehicle-meta', 'Chưa có xe nào gắn thiết bị GPS.'));
        return;
    }

    listEl.replaceChildren(...entries.map((entry) => {
        const v = entry.data;
        const signal = SIGNAL[signalState(v)];

        const dot = el('span', 'gps-dot');
        dot.style.background = signal.color;

        const title = el('div', 'gps-vehicle-title');
        title.append(dot, document.createTextNode(v.license_plate));

        const item = el('li', 'gps-vehicle-item');
        item.append(title, el('div', 'gps-vehicle-meta', `${signal.label} · ${formatTime(v.last_signal_at)}`));

        if (entry.marker) {
            item.addEventListener('click', () => {
                map.setView(entry.marker.getLatLng(), 17);
                entry.marker.openPopup();
            });
        } else {
            item.classList.add('gps-vehicle-item--disabled');
        }

        return item;
    }));
}

function fitToVehicles() {
    const points = [...vehicles.values()]
        .filter((entry) => entry.marker)
        .map((entry) => entry.marker.getLatLng());

    if (points.length) {
        map.fitBounds(L.latLngBounds(points), { padding: [40, 40], maxZoom: 16 });
    }
}

function showConnection(state) {
    connectionEl.dataset.state = state;
    connectionEl.textContent = CONNECTION_TEXT[state] ?? state;
}

// ---------- Khởi động ----------

async function init() {
    try {
        const positions = await fetchPositions(root.dataset.positionsUrl);
        positions.forEach(upsert);
        renderList();
        fitToVehicles();
    } catch (error) {
        listEl.replaceChildren(el('li', 'gps-vehicle-meta', error.message));
    }

    subscribeVehicleUpdates({
        onUpdate: (data) => {
            upsert(data);
            renderList();
        },
        onStatusChange: showConnection,
    });

    // Trạng thái "mất tín hiệu" phụ thuộc thời gian trôi qua, nên vẽ lại định kỳ
    setInterval(() => {
        vehicles.forEach(drawMarker);
        renderList();
    }, 15000);
}

init();
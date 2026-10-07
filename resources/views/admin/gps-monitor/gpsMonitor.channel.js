const CHANNEL = 'admin.monitor';

// Dấu chấm ở đầu: đây là tên tùy chỉnh đặt trong broadcastAs(), không phải tên class
const EVENT = '.vehicle.location.updated';

/**
 * C9.2 - Đăng ký kênh private, nhận vị trí mới theo thời gian thực.
 */
export function subscribeVehicleUpdates({ onUpdate, onStatusChange }) {
    if (!window.Echo) {
        onStatusChange('unavailable');
        return () => {};
    }

    const connection = window.Echo.connector.pusher.connection;
    connection.bind('state_change', ({ current }) => onStatusChange(current));
    onStatusChange(connection.state);

    window.Echo.private(CHANNEL)
        .listen(EVENT, onUpdate)
        .error(() => onStatusChange('denied'));

    return () => window.Echo.leave(CHANNEL);
}
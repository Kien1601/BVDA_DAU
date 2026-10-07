/**
 * C9.1 - Lấy vị trí mới nhất của mọi xe có gắn IMEI.
 */
export async function fetchPositions(url) {
    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Không tải được vị trí (HTTP ${response.status})`);
    }

    const json = await response.json();

    return json.data;
}
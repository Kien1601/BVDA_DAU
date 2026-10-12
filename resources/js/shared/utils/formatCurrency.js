const formatter = new Intl.NumberFormat('vi-VN');

/** 150000 -> "150.000 đ" (khớp App\Support\Money::vnd phía server) */
export function formatCurrency(amount) {
    return `${formatter.format(amount)} đ`;
}
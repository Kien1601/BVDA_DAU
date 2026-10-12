/** Chỉ gọi fn sau khi ngừng gọi đủ `wait` mili giây (dùng khi gõ, đổi giờ liên tục) */
export function debounce(fn, wait = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), wait);
    };
}
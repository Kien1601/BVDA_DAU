# Hướng dẫn giao diện — Rental GPS

Phiên bản 1 · Quyết định: Cách B (chia theo khu vực)

## 1. Nguồn tham khảo

Ngôn ngữ thiết kế lấy cảm hứng từ trang mẫu "Kage" (thư viện ThreeUI).
Chỉ tham khảo màu sắc, phông chữ, nhịp chữ và cách kẻ đường.

KHÔNG lấy: thương hiệu KAGE, logo, nội dung chữ, ảnh, phông riêng của trang mẫu,
cảnh 3D Three.js, file HTML gốc. Không tải tài nguyên từ máy chủ của ThreeUI.

## 2. Nguyên tắc chia khu vực

| Khu vực | Tinh thần | Được dùng | Không dùng |
|---|---|---|---|
| Trang khách hàng (`/`, xem xe, đặt thuê, tài khoản) | Tối, điện ảnh, gây ấn tượng | Nền tối, tiêu đề lớn viết hoa, hiện dần khi cuộn, ảnh lớn | Cảnh 3D, nhiễu hạt, con trỏ tùy chỉnh |
| Trang quản lý (`/admin`) | Công cụ: đọc nhanh, nhập nhanh | Sidebar + topbar tối; vùng nội dung nền sáng ngà; cùng phông, màu nhấn, kiểu nhãn, đường kẻ | Mọi hiệu ứng trang trí, chữ viết hoa cỡ lớn |

## 3. Màu (khai báo trong tailwind.config.js)

Màu nền, chữ, đường kẻ dùng **màu theo vai trò**, đổi theo chế độ sáng/tối: xem mục 10.
Hai màu nhấn cố định ở cả hai chế độ:

| Tên | Mã | Dùng cho |
|---|---|---|
| `vermilion` | `#e0231c` | **Màu nhấn duy nhất**: mục đang chọn, nút chính khi rê chuột, cảnh báo |
| `ember` | `#ff5a3c` | Trạng thái rê chuột của màu nhấn |

Quy tắc: đỏ son dùng tiết kiệm, mỗi màn hình chỉ vài điểm. Trạng thái nghiệp vụ
(xanh / vàng / xám) vẫn giữ màu riêng để dễ phân biệt, không thay bằng đỏ son.

## 4. Chữ

- Phông: **Onest** (Google Fonts), dự phòng `system-ui, sans-serif`.
- Nội dung: nét 300, cỡ 14–16px, giãn dòng 1.6.
- Nhãn nhỏ (eyebrow, tiêu đề cột bảng, nhãn form): 10–11px, nét 500, VIẾT HOA, `letter-spacing: .2em`.
- Tiêu đề trang khách: VIẾT HOA, nét 400, `letter-spacing: -.01em`, cỡ lớn theo `clamp()`.
- Tiêu đề trang admin: chữ thường, cỡ vừa, không viết hoa toàn bộ.
- Số liệu: cỡ lớn, nét 300, `font-variant-numeric: tabular-nums`; có thể kèm số thứ tự `01 02 03`.

## 5. Thành phần (resources/views/components)

| Thành phần | Kiểu dáng |
|---|---|
| Nút chính | Bo tròn dạng viên thuốc, viền mảnh; rê chuột thì nền trượt lên từ dưới |
| Nút phụ | Chỉ chữ + mũi tên trong vòng tròn viền mảnh |
| Ô nhập | Viền mảnh 1px, không bo nhiều, nhãn nhỏ viết hoa phía trên |
| Bảng | Không khung ngoài; chỉ kẻ ngang mảnh giữa các dòng; tiêu đề cột kiểu nhãn nhỏ |
| Thẻ số liệu | Nhãn nhỏ phía trên, số lớn nét mảnh phía dưới, viền mảnh |
| Nhãn trạng thái | Chấm tròn 6px + chữ nhỏ, không dùng khối màu đặc |
| Menu | Mục đang chọn có gạch chân hoặc vạch dọc màu đỏ son |

## 6. Chuyển động

- Thời gian 0.4–0.9s, đường cong `cubic-bezier(.16,1,.3,1)`.
- Trang khách: nội dung trượt lên 26px và hiện dần khi cuộn tới.
- Trang admin: chỉ chuyển màu khi rê chuột, không có hiệu ứng hiện dần.
- Tôn trọng `prefers-reduced-motion`: tắt toàn bộ chuyển động.

## 7. Bản đồ

Nền bản đồ chọn bằng biến `VITE_MAP_PROVIDER`. Trang khách có thể dùng nền tối
(Esri Dark Gray) cho đồng bộ; trang giám sát GPS giữ nền đường phố để dễ đọc.

## 8. Khả năng đọc

- Chữ đạt tương phản tối thiểu 4.5:1 ở cả hai chế độ (chữ `text-fg-soft` trở lên cho nội dung).
- Không đặt chữ nội dung bằng `text-fg-muted`; `fg-muted` chỉ cho nhãn nhỏ.

## 9. Hiệu ứng 3D (kế hoạch, làm cùng B1)

Tự viết bằng Three.js (cài qua npm, không dùng CDN), không dùng cảnh, ảnh
hay mã nguồn của ThreeUI. Nội dung cảnh gắn với đề tài: thành phố đêm, xe là
điểm sáng chạy trên đường, vệt sáng như tín hiệu GPS.

| Nơi dùng | Hiệu ứng | Chất lượng |
|---|---|---|
| Trang chủ khách | Cảnh "thành phố đêm", camera đi theo thao tác cuộn trang | Cao |
| Trang đăng nhập | Cùng cảnh, làm nền sau form | Thấp |
| Danh sách xe | Thẻ nghiêng theo chuột (CSS 3D) | — |
| Chi tiết xe | Ảnh nhiều lớp trượt lệch khi cuộn (CSS) | — |
| Dashboard admin | Thẻ số liệu nghiêng nhẹ, số chạy từ 0 | — |

Không đặt hiệu ứng 3D trên: bảng danh sách, form nhập liệu, bản đồ giám sát GPS.

Quy tắc kỹ thuật (bắt buộc):
- Cảnh viết một lần trong `resources/js/shared/scene/`, chọn chất lượng bằng tham số.
- Không có WebGL: hiện ảnh tĩnh thay thế, trang vẫn dùng bình thường.
- `prefers-reduced-motion`: cảnh đứng yên, không có camera bay.
- Điện thoại: giảm số đối tượng và độ phân giải.
- Dừng vẽ khi tab bị ẩn hoặc khi cảnh cuộn ra khỏi màn hình.
- Giải phóng toàn bộ geometry, material, texture khi rời trang.

## 10. Chế độ sáng/tối

### Màu theo vai trò

Mỗi tên màu chỉ có **một** nghĩa (nền, chữ, hay đường kẻ), giá trị đổi theo chế độ.
Biến CSS khai báo trong `resources/css/app.css`, dạng kênh RGB `r g b` để ghép độ trong suốt
(`bg-page/80`, `hover:border-fg/30`).

| Lớp Tailwind | Biến | Tối | Sáng | Vai trò |
|---|---|---|---|---|
| `bg-page` | `--t-page` | `5 7 10` | `244 246 243` | Nền trang |
| `bg-card` | `--t-card` | `10 14 18` | `255 255 255` | Nền thẻ, bảng, ô nhập, form |
| `bg-card-2` | `--t-card-2` | `17 24 32` | `238 241 237` | Nền khi rê chuột, vùng nhấn nhẹ, ô chỉ đọc |
| `text-fg` | `--t-fg` | `223 231 224` | `11 16 20` | Chữ chính |
| `text-fg-soft` | `--t-fg-soft` | `170 180 173` | `75 86 81` | Chữ phụ |
| `text-fg-muted` | `--t-fg-muted` | `120 131 124` | `108 118 113` | Nhãn nhỏ |
| `border-edge` | `--t-edge` + `--t-edge-a` | độ mờ `.13` | độ mờ `.10` | Đường kẻ thường |
| `border-edge-soft` | `--t-edge-soft-a` | `.07` | `.06` | Đường kẻ rất mờ |
| `border-edge-strong` | `--t-edge-strong-a` | `.24` | `.20` | Viền nút phụ, ô nhập |
| `bg-solid` | `--t-solid` | `223 231 224` | `11 16 20` | Nền nút chính |
| `text-on-solid` | `--t-on-solid` | `5 7 10` | `244 246 243` | Chữ trên nút chính |

`vermilion` và `ember` cố định ở cả hai chế độ (mục 3). Màu trạng thái nghiệp vụ (xanh/vàng/xám)
cũng giữ cố định.

### Quy tắc ghép cặp

- Nền nào đi với chữ đó: `bg-page` / `bg-card` / `bg-card-2` đi với `text-fg` / `text-fg-soft` / `text-fg-muted`;
  `bg-solid` đi với `text-on-solid`.
- Không bao giờ dùng `text-page`, `text-card` (chữ màu nền).
- **Ngoại lệ:** `bg-fg`, `bg-fg-soft`, `bg-fg-muted` chỉ được dùng cho **chấm và đường trang trí**
  (ví dụ chấm trạng thái 6px của `<x-status-badge>`), **không bao giờ làm nền đặt chữ lên**.
- Đường kẻ trang trí vẽ bằng nền (`h-px`) dùng `bg-edge-soft`, cùng màu với `border-edge-soft`.
- Chữ trên lớp đỏ son (nút chính khi rê chuột, vùng chọn chữ) dùng `text-white`, vì đỏ son không đổi theo chế độ.
- Chỗ nào bắt buộc viết CSS thường (danh sách do JavaScript dựng, popup bản đồ), dùng biến:
  `rgb(var(--t-card))`, `rgb(var(--t-fg) / .14)`, `rgb(var(--t-edge) / var(--t-edge-a))`. Không viết mã màu cứng.

### data-theme và data-theme-follow

- `data-theme="light|dark"` trên `<html>`: chế độ chung của trang. Script
  `layouts/partials/theme-boot.blade.php` (đầu `<head>`) đặt giá trị này trước khi trang vẽ:
  lấy `localStorage('theme')`, chưa có thì dùng `data-area-default` của khu vực
  (trang khách `dark`, khu quản lý `light`, trang đăng nhập `light`).
- Nút `<x-theme-toggle>` (xử lý trong `resources/js/shared/theme/theme.js`) đổi chế độ, lưu lựa chọn,
  đồng bộ giữa các tab và phát sự kiện `window` `theme:change` (`detail.theme`) cho phần tự vẽ màu
  (ví dụ bản đồ cửa hàng đổi nền `esri` ⇄ `esriDark`).
- `data-theme` đặt trên **một vùng con** thì vùng đó cố định chế độ, bất kể chế độ chung.
- `data-theme-follow` đặt trên vùng nằm **trong** vùng cố định nhưng vẫn phải theo chế độ chung
  (ví dụ thẻ form đăng nhập nằm trên nền thành phố đêm).

### Vùng luôn tối

| Vùng | Ở đâu |
|---|---|
| Thanh bên khu quản lý | `layouts/partials/admin-sidebar.blade.php` (`<aside data-theme="dark">`) |
| Màn hình mở đầu trang chủ | `customer/home/home.blade.php` (`section#hero`) |
| Header trang chủ khi còn nằm trên màn hình mở đầu | `layouts/partials/customer-header.blade.php` |
| Nền thành phố trang đăng nhập/đăng ký | `layouts/guest.blade.php` (thẻ form bên trong có `data-theme-follow`) |
| Khung ảnh thẻ xe (kiểu "studio") | `components/vehicle-card.blade.php` |

Cảnh 3D (`resources/js/shared/scene/`) luôn là cảnh đêm, không đổi màu theo chế độ.
Bản đồ giám sát GPS của nhân viên giữ nền đường phố, không đổi theo chế độ.

### Thêm giao diện mới

1. Chỉ dùng các lớp ở bảng trên, ghép đúng cặp; không dùng mã màu cứng, không dùng `bg-white`, `text-black`.
2. Ưu tiên component dùng chung (`<x-button>`, `<x-form.input>`, `<x-table>`...): chúng đã đúng ở cả hai chế độ.
3. Vùng cần cố định một chế độ: thêm `data-theme` lên thẻ ngoài cùng của vùng, ghi vào bảng "Vùng luôn tối".
4. Phần tự vẽ màu bằng JavaScript: đọc `document.documentElement.dataset.theme` lúc tạo và nghe `theme:change`.
5. Soát ở trang mẫu `/admin/ui` (chỉ máy dev): hai cột sáng/tối đặt cạnh nhau.

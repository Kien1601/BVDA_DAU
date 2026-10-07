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

| Tên | Mã | Dùng cho |
|---|---|---|
| `ink` | `#05070a` | Nền tối chính (trang khách, sidebar admin) |
| `ink-2` | `#0a0e12` | Nền tối nổi (thẻ, menu mở) |
| `bone` | `#dfe7e0` | Chữ chính trên nền tối |
| `bone-dim` | `#aab4ad` | Chữ phụ trên nền tối |
| `muted` | `#78837c` | Nhãn, chú thích |
| `paper` | `#f4f6f3` | Nền vùng nội dung admin |
| `vermilion` | `#e0231c` | **Màu nhấn duy nhất**: mục đang chọn, nút chính, cảnh báo |
| `ember` | `#ff5a3c` | Trạng thái rê chuột của màu nhấn |
| `line` | `rgba(223,231,224,.13)` | Đường kẻ trên nền tối |
| `line-soft` | `rgba(223,231,224,.07)` | Đường kẻ rất mờ trên nền tối |

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

- Chữ trên nền tối đạt tương phản tối thiểu 4.5:1 (chữ `bone-dim` trở lên cho nội dung).
- Không đặt chữ nội dung bằng màu `muted` trên nền tối; `muted` chỉ cho nhãn.

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
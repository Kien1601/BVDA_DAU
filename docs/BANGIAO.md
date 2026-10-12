# Tài liệu bàn giao — Rental GPS (bản 3, thay bản 2)

Cập nhật: sau khi xong B2 (tag v0.6-dat-thue).

Website Laravel cho thuê xe máy tại Đà Nẵng, có giám sát vị trí xe theo thời gian thực bằng GPS.
Đồ án sinh viên, phát triển trên Windows, chạy toàn bộ bằng Docker.

---

## 1. Trạng thái tổng quan

| Giai đoạn | Nội dung | Trạng thái |
|---|---|---|
| 1 | Tài liệu thiết kế | Đang làm: 7/19 sơ đồ |
| 2 | Xác thực, phân quyền 3 lớp | Xong |
| 3 | Module GPS real-time | Xong |
| 4 | Nghiệp vụ cho thuê | Đang làm: xong C3, C2, C6, B1, B2 + giao diện, sáng/tối, 3D |
| 5 | Triển khai server, Traccar, thiết bị GPS thật | Chưa bắt đầu |

Tag đã đánh: v0.3-gps, v0.4-danh-muc, v0.5-trang-khach, v0.6-dat-thue.

---

## 2. Công nghệ và môi trường

| Thành phần | Lựa chọn |
|---|---|
| Backend | Laravel 13, PHP 8.3, một ứng dụng duy nhất (Blade + Vite) |
| CSDL, queue | MySQL 8 (Docker volume), queue driver `database` |
| Xác thực | Laravel Breeze (Blade) + cột `role` + Gate đọc `config/permissions.php` |
| Real-time | Laravel Reverb (WebSocket), kênh private `admin.monitor` |
| Bản đồ | Leaflet; nguồn nền chọn qua `VITE_MAP_PROVIDER` (đang dùng `esri`, trang khách tối dùng `esriDark`) |
| 3D | Three.js (cài qua npm) |
| Giao diện | Tailwind + hệ màu theo vai trò, phông Onest |
| Nhận GPS | Traccar (bên thứ ba) forward HTTP JSON tới `/api/gps/update`; hiện dùng simulator |
| Múi giờ | `APP_TIMEZONE=Asia/Ho_Chi_Minh` |

### Docker local (`docker-compose.local.yml`)

| Container | Cổng | Ghi chú |
|---|---|---|
| rental_app | — | PHP-FPM |
| rental_nginx | 8000 | Web: http://localhost:8000 |
| rental_db | 3307 | MySQL, DB `rental_gps`, user `rental` |
| rental_redis | — | |
| rental_reverb | 8081 → 8080 | Cổng Windows 8080 bị chương trình khác chiếm |
| rental_queue | — | `queue:work` |

`.env`: `REVERB_HOST=reverb`, `REVERB_PORT=8080`, `VITE_REVERB_HOST=localhost`, `VITE_REVERB_PORT=8081`,
`BROADCAST_CONNECTION=reverb`, `QUEUE_CONNECTION=database`, `GPS_API_TOKEN=...`, `GPS_SPEED_UNIT=knots`.

### Lệnh (PHP và Node KHÔNG cài trên Windows)

| Việc | Lệnh |
|---|---|
| Artisan | `art <lệnh>` = `docker compose -f docker-compose.local.yml exec app php artisan <lệnh>` |
| Test | `art test` |
| Build giao diện | `docker run --rm -v ${PWD}:/app -w /app node:22 sh -c "npm run build"` |
| Giả lập GPS | `dexec php simulator/fake-gps.php [--interval=5]` |
| Dọn đơn quá hạn giữ chỗ | `art rental:expire-pending` |

Hàm tắt `art`, `dexec`, `npmd` lưu trong `$PROFILE` của PowerShell.

### Tài khoản mẫu (mật khẩu `password`)

| Email | Vai trò |
|---|---|
| admin@rental.test | admin |
| staff@rental.test | staff |
| (tự đăng ký ở /register) | customer |

---

## 3. Quy ước dự án (chi tiết trong CLAUDE.md)

- Controller mỏng, Service dày (`app/Services`). Lỗi nghiệp vụ ném `ValidationException` theo tên trường.
- Quyền chỉ khai báo ở `config/permissions.php`; kiểm tra qua middleware `can:` và Gate/Policy.
- View theo chức năng: `resources/views/{admin,customer}/<chức-năng>/`; JS riêng `<tên>.page.js` cạnh view,
  phải khai báo trong mảng `input` của `vite.config.js`. JS/CSS dùng chung: `resources/js/shared/`.
- Trang khách TUYỆT ĐỐI không nhận `gps_device_id`, `last_lat`, `last_lng`, `last_signal_at`.
- Nhãn, thông báo tiếng Việt. Tên test: tiếng Việt không dấu, snake_case.
- Migration: tạo → dán nội dung → LƯU FILE → rồi mới `art migrate`.

### Đặt route trong `routes/web.php`

| Loại | Vị trí |
|---|---|
| Trang khách công khai | Phần "TRANG KHÁCH" đầu file |
| Khách đã đăng nhập | Nhóm `Route::middleware('auth')` phần "ĐẶT THUÊ" (ngoài nhóm /admin) |
| Nhân viên, quản trị | Bên trong nhóm `prefix('admin')->name('admin.')` |

---

## 4. Phân quyền

Ba lớp: middleware route (`auth`, `role`) → Gate/Policy → `@can` trong Blade.
Đăng ký công khai luôn là customer; tài khoản `is_active = false` không đăng nhập được.

| Quyền | customer | staff | admin |
|---|:-:|:-:|:-:|
| booking.create, order.view_own | ✔ | | |
| dashboard.view, gps.monitor, order.manage, customer.view | | ✔ | ✔ |
| vehicle.manage, vehicle.delete, store.manage, pricing.manage, user.manage, report.view | | | ✔ |

Policy: `RentalOrderPolicy` (view: chủ đơn hoặc order.manage; cancel: chủ đơn khi pending/approved).

---

## 5. Cơ sở dữ liệu hiện tại

| Bảng | Điểm chính |
|---|---|
| users | + phone, role, id_card_number, violation_count, is_active |
| stores | name, address, latitude, longitude, phone |
| vehicles | type ∈ {Xe số, Tay ga, Côn tay, Xe điện}; status available/rented/maintenance; gps_device_id (IMEI, unique); last_signal_at, last_device_time, last_lat, last_lng; xóa mềm |
| gps_locations | vehicle_id, lat, lng, speed (km/h), device_time, received_at |
| promotions | code (chữ hoa, unique), discount_percent HOẶC discount_amount, start_date, end_date, is_active; trạng thái tính từ ngày |
| rental_orders | user_id, vehicle_id, promotion_id (restrict), start_time, end_time, actual_return_time, status, subtotal_amount, discount_amount, total_amount, deposit_amount, surcharge_amount, expires_at, cancelled_at, cancel_reason, note |

Chưa có: payments (B3), violations (C4, đã chốt làm), connection_alerts, settings (C10).

---

## 6. Chức năng

### Đã xong

| Mã | Chức năng | Ghi chú |
|---|---|---|
| A1–A4 | Đăng ký, đăng nhập, đăng xuất, hồ sơ | Breeze; chuyển hướng theo vai trò |
| B1 | Trang chủ, danh sách và lọc xe, chi tiết xe, cửa hàng | `/`, `/vehicles`, `/vehicles/{id}`, `/stores` |
| B2 | Đặt thuê | Báo giá trực tiếp, mã khuyến mãi, giữ chỗ 30 phút, hủy đơn |
| C1 | Dashboard vận hành | Số liệu thật; doanh thu chờ B3/C8 |
| C2 | Quản lý xe | Ảnh, gắn/gỡ IMEI, đổi trạng thái, xóa mềm (gỡ IMEI) |
| C3 | Quản lý cửa hàng | Chọn tọa độ trên bản đồ |
| C6 | Giá thuê, khuyến mãi | Áp giá theo loại xe, sửa giá từng xe; mã % hoặc số tiền |
| C9 | Giám sát GPS | Bản đồ real-time, popup, trạng thái mất tín hiệu sau 2 phút |
| D1–D5 | Module định vị | Token, IMEI, lưu lịch sử, gói tin đến muộn, event qua queue, simulator |

### Chưa làm

| Mã | Chức năng |
|---|---|
| C5 | Quản lý đơn: duyệt, giao xe, gia hạn, trả xe + phụ thu, hủy (**đề xuất làm tiếp theo**) |
| B3 | Thanh toán: VNPay/Momo sandbox, tiền mặt tại cửa hàng, callback |
| B4 | Tài khoản khách: lịch sử đơn, chi tiết, hóa đơn |
| C4 | Khách hàng, vi phạm (bảng violations) |
| C7 | Quản lý nhân viên (tạo, sửa, khóa) |
| C8 | Báo cáo doanh thu, lượt thuê, xe thuê nhiều |
| C10 | Cảnh báo mất kết nối (làm sau, ngoài phạm vi) |

---

## 7. Nghiệp vụ đặt thuê (B2) — `app/Services/BookingService.php`

- Tiền thuê: giờ (lẻ phút tròn lên) → ngày → tuần. Giờ lẻ không đắt hơn 1 ngày; ngày lẻ không đắt hơn 1 tuần.
- Cọc: 30% tổng sau giảm, tròn lên 10.000 đ, tối thiểu 100.000 đ.
- Trùng lịch: chặn bởi đơn pending (còn hạn giữ chỗ), approved, active. Khoảng nối tiếp không tính trùng.
- Thời gian: nhận xe cách hiện tại ≥ 30 phút; trả sau nhận; tối đa 30 ngày.
- `create()` khóa dòng xe (`lockForUpdate`) chống đặt trùng đồng thời.
- `quote(..., $ignoreOrderId)` dùng lại cho gia hạn (C5.3).
- Cấu hình: `config/rental.php`.

---

## 8. Module GPS

Luồng: thiết bị/simulator → `POST /api/gps/update` (Bearer token) → `GpsUpdateRequest` → `GpsService`
(`TraccarPayloadMapper`: knots → km/h) → lưu `gps_locations`, cập nhật `vehicles.last_*` (bỏ qua gói đến muộn)
→ event `VehicleLocationUpdated` → queue → Reverb → bản đồ (Echo + Leaflet).
Lỗi: token sai 401, dữ liệu sai 422, IMEI lạ 404.

---

## 9. Giao diện

- Hướng dẫn đầy đủ: `docs/design/huong-dan-giao-dien.md`.
- Cách B: trang khách phong cách điện ảnh; khu quản lý ưu tiên đọc nhanh, sidebar luôn tối.
- Màu theo vai trò, luôn dùng theo cặp: `bg-page`, `bg-card`, `bg-card-2`, `text-fg`, `text-fg-soft`,
  `text-fg-muted`, `border-edge(-soft/-strong)`, `bg-solid` + `text-on-solid`; nhấn `vermilion`, `ember`.
  Không dùng `bg-white`, không viết cứng mã màu.
- Chế độ sáng/tối toàn dự án (nút `x-theme-toggle`, localStorage). Mặc định: khách tối, quản lý sáng.
  Vùng luôn tối: chỉ sidebar quản lý (`data-theme="dark"`). Chế độ sáng có tương phản cao (máy chiếu).
- Component: button (primary, secondary, link, link-danger, light, outline-light), form/input, form/select,
  form/image-upload, table, stat-card, page-header, status-badge, vehicle-card, pagination, theme-toggle.
- Hiệu ứng trang khách: hiện dần khi cuộn, thẻ nghiêng theo chuột, ảnh nhiều lớp.
- Cảnh 3D "thành phố": `resources/js/shared/scene/cityScene.js`, hai phiên bản ngày/đêm theo chế độ,
  chuyển dần 1,2 giây; mức high (trang chủ), low (đăng nhập).
- Trang mẫu `/admin/ui` (chỉ local, admin): mọi component và cảnh 3D ở hai chế độ cạnh nhau.
  Thêm component mới thì cập nhật trang mẫu.

---

## 10. Test

| Nhóm | File |
|---|---|
| Phân quyền | PermissionTest |
| GPS | GpsUpdateTest, MonitorAccessTest |
| Danh mục | StoreManagementTest, VehicleManagementTest, PricingTest, PromotionTest |
| Trang khách | AuthPageTest, CustomerBrowseTest, ThemeTest |
| Đặt thuê | BookingServiceTest (14), BookingFlowTest (10) |
| Có sẵn | Test của Breeze, ExampleTest (đã thêm RefreshDatabase) |

Môi trường test: SQLite trong RAM, `BROADCAST_CONNECTION=null`; test có giao diện gọi `withoutVite()`.
Phải build giao diện trước khi chạy test có render trang.

---

## 11. Git

- Mỗi chức năng một nhánh `feature/<tên>`; test xanh mới gộp `main`; tài liệu commit thẳng `main`.
- Thao tác rủi ro: sao lưu sang nhánh riêng trước. Nhánh `backup/sang-toi-loi` (local) có thể xóa.
- Claude Code: chỉ commit, không push, không reset --hard, không sửa .env/Docker.

---

## 12. Việc còn treo

| Việc | Khi nào |
|---|---|
| Khách thanh toán hoặc chọn trả tại cửa hàng → `expires_at = null`; hoàn tiền khi hủy đơn đã trả | B3 |
| Popup bản đồ GPS hiện tên khách đang thuê (cột renter đang null) | C5 |
| Doanh thu dashboard | B3, C8 |
| Dữ liệu Đà Nẵng: cửa hàng, biển số đầu 43, tọa độ mặc định (MapMonitorController, location-picker), tuyến giả lập | Khi tích hợp GPS thật |
| Container scheduler (chạy rental:expire-pending tự động) | Giai đoạn 5 |
| Sơ đồ còn thiếu: Sequence đặt thuê, thanh toán, duyệt/giao xe, trả xe, thêm xe; Class; Activity; State; triển khai | Song song |

---

## 13. Lỗi hay gặp và cách xử lý nhanh

| Hiện tượng | Xử lý |
|---|---|
| `art` không nhận lệnh | Hàm chưa có trong cửa sổ PowerShell: nạp lại `$PROFILE` |
| `Route [...] not defined` | Route đặt sai nhóm (thường là lọt vào nhóm /admin): `art route:list --name=...` |
| `Unknown column` sau migrate | Chạy migrate trước khi lưu file: `migrate:rollback --step=1`, kiểm tra file, `migrate` |
| `Unable to locate file in Vite manifest` | Chưa khai báo trong vite.config.js hoặc chưa build |
| `Cannot find native binding` (npm) | Xóa node_modules + package-lock.json, cài lại bằng container node:22 |
| Container kẹt `Created` / port đã dùng | Đổi cổng phía Windows; `net stop winnat` + `net start winnat` (Admin) |
| `getaddrinfo for db failed` | Container khác mạng: `down` rồi `up -d` (KHÔNG `-v`) |
| File Blade lỗi lạ đầu trang | Ghi file có BOM: dùng UTF-8 không BOM |
| Chữ trùng nền | Dùng sai token màu: kiểm tra ở /admin/ui |


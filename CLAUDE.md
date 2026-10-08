<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>


# Rental GPS — hướng dẫn cho Claude Code

Website Laravel cho thuê xe máy + giám sát GPS real-time. Đồ án sinh viên, máy dev Windows, chạy bằng Docker.

## Lệnh chạy (PHP và Node KHÔNG cài trên Windows, luôn chạy qua Docker)

- Artisan:  docker compose -f docker-compose.local.yml exec -T app php artisan <lệnh>
- Test:     docker compose -f docker-compose.local.yml exec -T app php artisan test
- Build JS/CSS: docker run --rm -v "${PWD}:/app" -w /app node:22 sh -c "npm run build"
- npm install: docker run --rm -v "${PWD}:/app" -w /app node:22 sh -c "npm install <gói>"
- Xóa cache view: docker compose -f docker-compose.local.yml exec -T app php artisan view:clear
- KHÔNG chạy npm trực tiếp trên Windows (làm hỏng node_modules phần biên dịch cho Linux).
- Phải build lại trước khi chạy test, nếu có thay đổi file trong vite.config.js input.

## Cấu trúc và quy ước

- Controller mỏng, Service dày (app/Services). Quyền chỉ khai báo ở config/permissions.php, kiểm tra qua Gate/`can:`.
- Route khu quản lý nằm TRONG nhóm prefix('admin') với middleware ['auth', 'role:staff,admin'].
- View tổ chức theo chức năng: resources/views/{admin,customer}/<chức-năng>/, JS riêng của trang: <tên>.page.js
  cạnh view và phải được khai báo trong vite.config.js (mảng input).
- Code JS/CSS dùng chung ở resources/js/shared/ (chỉ khi ≥ 2 chức năng dùng).
- Thông báo lỗi, nhãn giao diện: tiếng Việt. Tên hàm test: tiếng Việt không dấu, snake_case.
- Trang khách TUYỆT ĐỐI không được nhận gps_device_id, last_lat, last_lng, last_signal_at của xe.
- Giao diện theo docs/design/huong-dan-giao-dien.md. Component dùng chung ở resources/views/components.

## Git

- Mỗi chức năng một nhánh feature/<tên>, commit nhỏ, thông điệp tiếng Việt không dấu.
- KHÔNG push, KHÔNG git reset --hard, KHÔNG push --force, KHÔNG xóa nhánh. Việc đó người dùng tự làm.
- KHÔNG sửa .env, docker-compose*.yml, Dockerfile, migration đã chạy.
- KHÔNG sửa assertion của test có sẵn để test "xanh". Test đỏ thì sửa code, hoặc dừng lại báo cáo.
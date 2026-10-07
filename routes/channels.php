<?php

use Illuminate\Support\Facades\Broadcast;

// Chỉ ai có quyền gps.monitor (staff, admin) mới đăng ký được kênh vị trí xe.
// Dùng Gate, không viết cứng role: đúng quy tắc "quyền chỉ khai báo ở config/permissions.php".
Broadcast::channel('admin.monitor', fn ($user) => $user->can('gps.monitor'));
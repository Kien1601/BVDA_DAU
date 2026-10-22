<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

// Chỉ ai có quyền gps.monitor (staff, admin) mới đăng ký được kênh vị trí xe.
// Dùng Gate, không viết cứng role: đúng quy tắc "quyền chỉ khai báo ở config/permissions.php".
Broadcast::channel('admin.monitor', fn ($user) => $user->can('gps.monitor'));
Broadcast::channel('App.Models.User.{id}', fn ($user, $id) => (int) $user->id === (int) $id);
// Kênh chung của nhân viên: nhận mọi tin nhắn mới
Broadcast::channel('chat.staff', fn ($user) => $user->can('chat.manage'));

// Kênh của 1 hội thoại: chính khách đó hoặc nhân viên
Broadcast::channel('chat.{conversationId}', fn ($user, $conversationId) =>
    $user->can('chat.manage')
    || Conversation::whereKey($conversationId)->where('customer_id', $user->id)->exists()
);
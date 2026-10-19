<?php

/*
| Quy tắc cho thuê (B2). Đổi chính sách ở đây, không sửa code.
*/
return [
    // Đơn chờ duyệt chưa thanh toán được giữ chỗ bao lâu (phút)
    'hold_minutes' => (int) env('RENTAL_HOLD_MINUTES', 30),

    // Nhận xe phải cách hiện tại ít nhất bao nhiêu phút (để cửa hàng chuẩn bị xe)
    'min_lead_minutes' => 30,

    // Mỗi đơn thuê tối đa bao nhiêu ngày
    'max_days' => 30,

    // Tiền cọc = % tổng tiền sau giảm, làm tròn lên theo bước, không thấp hơn mức tối thiểu
    'deposit_percent' => 30,
    'deposit_round' => 10000,
    'deposit_min' => 100000,

    // C5: giao xe sớm nhất bao nhiêu phút trước giờ nhận
    'pickup_early_minutes' => 60,
    // C5: trả trễ trong khoảng này không tính phụ thu
    'late_grace_minutes' => 15,
    // C5: giao xe trễ quá bao nhiêu phút thì tính phụ thu
    'alert_pickup_before_minutes' => 60,
    'alert_pickup_late_minutes' => 30,
    'alert_return_before_minutes' => 60,
    'early_return_min_minutes' => 30,

        // A5: đăng nhập bằng mã OTP
    'otp' => [
        'length' => 6,
        'ttl_minutes' => 5,         // mã hết hạn sau 5 phút
        'max_attempts' => 5,        // nhập sai 5 lần thì hủy mã
        'resend_seconds' => 60,     // chờ 60 giây mới gửi lại
        'max_per_hour' => 5,        // tối đa 5 mã mỗi giờ cho một email/SĐT
    ],

    
];
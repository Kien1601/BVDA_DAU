<?php

/*
 * Quyền theo vai trò.
 * Theo góp ý của giảng viên hướng dẫn: staff và admin dùng chung mọi chức năng quản lý,
 * riêng quản lý tài khoản nhân viên (user.manage) chỉ admin.
 */
return [
    'booking.create'   => ['customer'],
    'order.view_own'   => ['customer'],
    'chat.use'   => ['customer'],
    'dashboard.view'   => ['staff', 'admin'],
    'gps.monitor'      => ['staff', 'admin'],
    'chat.manage'       => ['staff', 'admin'],
    'order.manage'     => ['staff', 'admin'],
    'customer.view'    => ['staff', 'admin'],
    'customer.lock'    => ['staff', 'admin'],
    'violation.create' => ['staff', 'admin'],
    'violation.delete' => ['staff', 'admin'],
    'vehicle.manage'   => ['staff', 'admin'],
    'vehicle.delete'   => ['staff', 'admin'],
    'store.manage'     => ['staff', 'admin'],
    'pricing.manage'   => ['staff', 'admin'],
    'report.view'      => ['staff', 'admin'],

    'user.manage'      => ['admin'],
];
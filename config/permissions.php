<?php

return [
    'booking.create' => ['customer'],
    'order.view_own' => ['customer'],
    'gps.monitor'    => ['staff', 'admin'],
    'order.manage'   => ['staff', 'admin'],
    'customer.view'  => ['staff', 'admin'],
    'vehicle.manage' => ['admin'],
    'vehicle.delete' => ['admin'],
    'store.manage'   => ['admin'],
    'pricing.manage' => ['admin'],
    'user.manage'    => ['admin'],
    'report.view'    => ['admin'],
];
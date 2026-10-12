<?php

namespace App\Policies;

use App\Models\RentalOrder;
use App\Models\User;

class RentalOrderPolicy
{
    /** B4.2 - khách xem đơn của chính mình; nhân viên, quản trị xem mọi đơn */
    public function view(User $user, RentalOrder $order): bool
    {
        return $order->user_id === $user->id || $user->can('order.manage');
    }

    /** B2.5 - chỉ chủ đơn, và chỉ khi đơn chưa bắt đầu thuê */
    public function cancel(User $user, RentalOrder $order): bool
    {
        return $order->user_id === $user->id && $order->status->cancellableByCustomer();
    }
}
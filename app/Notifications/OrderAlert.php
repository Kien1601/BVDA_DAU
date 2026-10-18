<?php

namespace App\Notifications;

use App\Enums\AlertKind;
use App\Models\RentalOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderAlert extends Notification
{
    use Queueable;

    public function __construct(public RentalOrder $order, public AlertKind $kind) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind->value,
            'tone' => $this->kind->tone(),
            'order_id' => $this->order->id,
            'title' => $this->kind->label().' · Đơn #'.$this->order->id,
            'message' => $this->message(),
            'url' => route('admin.orders.show', $this->order, false),
        ];
    }

    private function message(): string
    {
        $o = $this->order;
        $who = $o->user->name.' · '.$o->vehicle->name.' ('.$o->vehicle->license_plate.')';

        return match ($this->kind) {
            AlertKind::PickupSoon => "{$who} nhận xe lúc ".$o->start_time->format('H:i d/m').'.',
            AlertKind::PickupLate => "{$who} chưa đến nhận xe, hẹn lúc ".$o->start_time->format('H:i d/m').'.',
            AlertKind::ReturnSoon => "{$who} trả xe lúc ".$o->end_time->format('H:i d/m').'.',
            AlertKind::Overdue => "{$who} quá hạn trả từ ".$o->end_time->format('H:i d/m').'.',
            AlertKind::NewBooking => "{$who} đặt xe ".$o->start_time->format('H:i d/m').' đến '.$o->end_time->format('H:i d/m').'.',
            AlertKind::DepositPaid => "{$who} đã thanh toán cọc, đơn chờ duyệt.",
            AlertKind::CustomerCancelled => "{$who} tự hủy đơn nhận xe lúc ".$o->start_time->format('H:i d/m').'.',
        };
    }
}
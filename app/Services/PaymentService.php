<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\RentalOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Enums\AlertKind;


class PaymentService
{
    /** Khách chọn trả cọc tiền mặt tại cửa hàng: đơn không còn hết hạn giữ chỗ */
    public function chooseCashAtStore(RentalOrder $order): Payment
    {
        return DB::transaction(function () use ($order) {
            $order = $this->lockOrder($order);
            $this->ensureCanPayDeposit($order);

            $existing = $order->payments()
                ->where('type', PaymentType::Deposit->value)
                ->where('method', PaymentMethod::Cash->value)
                ->where('status', PaymentStatus::Pending->value)
                ->first();

            if ($existing) {
                return $existing;
            }

            $payment = $this->createPayment($order, PaymentType::Deposit, PaymentMethod::Cash, $order->deposit_amount);
            $order->forceFill(['expires_at' => null])->save();

            return $payment;
        });
    }

    /** Tạo khoản cọc chờ trả online; B3.3 (VNPay) và B3.4 (MoMo) dùng hàm này */
    public function startOnlineDeposit(RentalOrder $order, PaymentMethod $method): Payment
    {
        return DB::transaction(function () use ($order, $method) {
            $order = $this->lockOrder($order);
            $this->ensureCanPayDeposit($order);

            // Link online cũ chưa trả thì bỏ, mỗi lần bấm thanh toán tạo mã mới
            $order->payments()
                ->where('type', PaymentType::Deposit->value)
                ->where('method', '!=', PaymentMethod::Cash->value)
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Cancelled->value]);

            return $this->createPayment($order, PaymentType::Deposit, $method, $order->deposit_amount);
        });
    }

       /** Ghi nhận thanh toán thành công. Cổng có thể gọi về nhiều lần nên phải chạy lặp lại được */
    public function markPaid(Payment $payment, ?string $providerTxnId = null, array $payload = [], ?User $staff = null): Payment
    {
        return DB::transaction(function () use ($payment, $providerTxnId, $payload, $staff) {
            $payment = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($payment->status === PaymentStatus::Paid) {
                return $payment;
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'provider_txn_id' => $providerTxnId ?? $payment->provider_txn_id,
                'payload' => $payload ?: $payment->payload,
                'created_by' => $staff?->id ?? $payment->created_by,
            ]);

            $order = $payment->order;

            if ($payment->type === PaymentType::Deposit) {
                // Đã có cọc thì các khoản cọc đang chờ khác (tiền mặt, link cũ) bỏ đi
                $order->payments()
                    ->whereKeyNot($payment->getKey())
                    ->where('type', PaymentType::Deposit->value)
                    ->where('status', PaymentStatus::Pending->value)
                    ->update(['status' => PaymentStatus::Cancelled->value]);

                if ($order->status === OrderStatus::Cancelled) {
                    // Tiền về sau khi đơn đã bị hủy (hết giữ chỗ trong lúc khách đang trả): hoàn lại
                    $this->createRefund($order, $payment->amount, $payment->method, null, 'Đơn đã hủy trước khi nhận tiền cọc');
                } else {
                    $order->forceFill(['expires_at' => null])->save();

                    // C9.2: khách tự trả cọc online thì báo nhân viên; nhân viên thu tiền mặt thì không cần
                    if ($staff === null) {
                        app(OrderAlertService::class)->notifyEvent($order, AlertKind::DepositPaid);
                    }
                }
            }

            return $payment;
        });
    }

    public function markFailed(Payment $payment, array $payload = []): Payment
    {
        if ($payment->status === PaymentStatus::Pending) {
            $payment->update(['status' => PaymentStatus::Failed, 'payload' => $payload ?: $payment->payload]);
        }

        return $payment;
    }

    /** Nhân viên thu tiền mặt: cọc (nếu khách chọn trả tại cửa hàng) hoặc phần còn lại khi trả xe */
    public function collectCash(RentalOrder $order, PaymentType $type, int $amount, User $staff, ?string $note = null): Payment
    {
        if ($type === PaymentType::Refund) {
            throw ValidationException::withMessages(['payment' => 'Không thu tiền với loại hoàn tiền.']);
        }
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Số tiền phải lớn hơn 0.']);
        }
        if ($order->status === OrderStatus::Cancelled) {
            throw ValidationException::withMessages(['payment' => 'Đơn đã hủy, không thu tiền.']);
        }
                if ($type === PaymentType::Deposit
            && in_array($order->status, [OrderStatus::Pending, OrderStatus::Approved], true)
            && now()->gte($order->end_time)) {
            throw ValidationException::withMessages(['payment' => 'Đơn đã quá giờ trả, không thu cọc. Hãy hủy đơn.']);
        }

        if ($type === PaymentType::Deposit) {
            $pendingCash = $order->payments()
                ->where('type', PaymentType::Deposit->value)
                ->where('method', PaymentMethod::Cash->value)
                ->where('status', PaymentStatus::Pending->value)
                ->first();

            if ($pendingCash) {
                $pendingCash->update(['amount' => $amount, 'note' => $note]);

                return $this->markPaid($pendingCash, null, [], $staff);
            }
        }

        $payment = $this->createPayment($order, $type, PaymentMethod::Cash, $amount, $staff, $note);

        return $this->markPaid($payment, null, [], $staff);
    }

    /** Số tiền khách còn phải trả: tổng + phụ thu − đã trả */
    public function amountDue(RentalOrder $order): int
    {
        return max(0, $order->total_amount + $order->surcharge_amount - $order->paidAmount());
    }

    /** Gọi khi đơn bị hủy: bỏ các khoản đang chờ, nếu đã trả tiền thì tạo khoản hoàn */
    public function handleCancelled(RentalOrder $order, ?User $staff, string $reason): ?Payment
    {
        $order->payments()
            ->where('status', PaymentStatus::Pending->value)
            ->where('type', '!=', PaymentType::Refund->value)
            ->update(['status' => PaymentStatus::Cancelled->value]);

        $paid = $order->paidAmount();
        if ($paid <= 0) {
            return null;
        }

        $lastPaid = $order->payments()
            ->where('status', PaymentStatus::Paid->value)
            ->where('type', '!=', PaymentType::Refund->value)
            ->latest('paid_at')
            ->first();

        return $this->createRefund($order, $paid, $lastPaid->method, $staff, $reason);
    }

    /** C9.6 - Tổng tiền giảm sau khi tính lại mà khách đã trả nhiều hơn: hoàn phần dư */
public function refundOverpaid(RentalOrder $order, ?User $staff, string $reason): ?Payment
{
    $over = $order->paidAmount() - ($order->total_amount + $order->surcharge_amount);
    if ($over <= 0) {
        return null;
    }

    $lastPaid = $order->payments()
        ->where('status', PaymentStatus::Paid->value)
        ->where('type', '!=', PaymentType::Refund->value)
        ->latest('paid_at')
        ->first();

    return $this->createRefund($order, $over, $lastPaid->method, $staff, $reason);
}

    /** Nhân viên xác nhận đã hoàn tiền cho khách */
    public function confirmRefund(Payment $refund, User $staff): Payment
    {
        if ($refund->type !== PaymentType::Refund || $refund->status !== PaymentStatus::Pending) {
            throw ValidationException::withMessages(['payment' => 'Khoản này không phải hoàn tiền đang chờ.']);
        }

        return $this->markPaid($refund, null, [], $staff);
    }

    private function createRefund(RentalOrder $order, int $amount, PaymentMethod $method, ?User $staff, string $reason): Payment
    {
        return $this->createPayment($order, PaymentType::Refund, $method, $amount, $staff, $reason);
    }

    private function createPayment(
        RentalOrder $order,
        PaymentType $type,
        PaymentMethod $method,
        int $amount,
        ?User $staff = null,
        ?string $note = null,
    ): Payment {
        return $order->payments()->create([
            'type' => $type,
            'method' => $method,
            'amount' => $amount,
            'status' => PaymentStatus::Pending,
            'txn_ref' => 'RG'.now()->format('ymdHis').Str::upper(Str::random(6)),
            'created_by' => $staff?->id,
            'note' => $note,
        ]);
    }

    private function ensureCanPayDeposit(RentalOrder $order): void
    {
        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Approved], true)) {
            throw ValidationException::withMessages(['payment' => 'Đơn này không còn nhận thanh toán cọc.']);
        }
        if ($order->status === OrderStatus::Pending && $order->expires_at && $order->expires_at->isPast()) {
            throw ValidationException::withMessages(['payment' => 'Đơn đã hết thời gian giữ chỗ.']);
        }
        if ($order->depositPaid()) {
            throw ValidationException::withMessages(['payment' => 'Đơn đã trả cọc.']);
        }
    }

    private function lockOrder(RentalOrder $order): RentalOrder
    {
        return RentalOrder::whereKey($order->getKey())->lockForUpdate()->firstOrFail();
    }
}
<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalOrder extends Model
{
    protected $fillable = [
        'user_id', 'vehicle_id', 'promotion_id', 'start_time', 'end_time', 'status',
        'subtotal_amount', 'discount_amount', 'total_amount', 'deposit_amount',
        'expires_at', 'note',
        // C5
        'approved_at', 'approved_by', 'picked_up_at', 'picked_up_by',
        'returned_by', 'cancelled_by', 'original_end_time', 'surcharge_note',
        'actual_return_time', 'surcharge_amount', 'cancelled_at', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'actual_return_time' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'approved_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'original_end_time' => 'datetime',
            'status' => OrderStatus::class,
            'subtotal_amount' => 'integer',
            'discount_amount' => 'integer',
            'total_amount' => 'integer',
            'deposit_amount' => 'integer',
            'surcharge_amount' => 'integer',
            'early_return_at' => 'datetime',
            'early_return_requested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Xe đã xóa mềm vẫn hiện trong lịch sử đơn */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function pickedUpBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_up_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Đã trả cọc thành công chưa */
    public function depositPaid(): bool
    {
        return $this->payments()
            ->where('type', PaymentType::Deposit->value)
            ->where('status', PaymentStatus::Paid->value)
            ->exists();
    }

    /** Tổng tiền khách đã trả, trừ đi tiền đã hoàn */
    public function paidAmount(): int
    {
        $paid = $this->payments()->where('status', PaymentStatus::Paid->value);

        $in = (clone $paid)->where('type', '!=', PaymentType::Refund->value)->sum('amount');
        $out = (clone $paid)->where('type', PaymentType::Refund->value)->sum('amount');

        return (int) $in - (int) $out;
    }

    /**
     * Đơn đang giữ xe: chờ duyệt (còn hạn giữ chỗ), đã duyệt, đang thuê.
     * Đơn chờ duyệt quá hạn bị coi như đã nhả xe NGAY, kể cả khi lệnh dọn chưa kịp chạy.
     */
    public function scopeBlocking(Builder $query): Builder
    {
        return $query
            ->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, OrderStatus::blocking()))
            ->where(fn ($q) => $q
                ->where('status', '!=', OrderStatus::Pending->value)
                ->orWhereNull('expires_at')
                ->orWhere('expires_at', '>', now()));
    }

    /** Hai khoảng thời gian chồng nhau; khoảng nối tiếp (A kết thúc đúng lúc B bắt đầu) KHÔNG tính là chồng */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->where('start_time', '<', $end)->where('end_time', '>', $start);
    }
}
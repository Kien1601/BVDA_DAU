<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalOrder extends Model
{
    protected $fillable = [
        'user_id', 'vehicle_id', 'promotion_id', 'start_time', 'end_time', 'status',
        'subtotal_amount', 'discount_amount', 'total_amount', 'deposit_amount',
        'expires_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'actual_return_time' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => OrderStatus::class,
            'subtotal_amount' => 'integer',
            'discount_amount' => 'integer',
            'total_amount' => 'integer',
            'deposit_amount' => 'integer',
            'surcharge_amount' => 'integer',
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
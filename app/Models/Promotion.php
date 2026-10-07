<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    /** Trạng thái hiển thị: [chữ, màu chấm của <x-status-badge>] */
    public const STATES = [
        'active'    => ['Đang áp dụng', 'success'],
        'scheduled' => ['Sắp diễn ra',  'info'],
        'expired'   => ['Hết hạn',      'neutral'],
        'disabled'  => ['Đã tắt',       'warning'],
    ];

    protected $fillable = [
        'code', 'discount_percent', 'discount_amount', 'start_date', 'end_date', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'discount_percent' => 'integer',
            'discount_amount' => 'integer',
        ];
    }

    /** Trạng thái của mã vào một ngày (mặc định hôm nay). Ngày kết thúc được tính là còn hiệu lực. */
    public function state(?CarbonInterface $on = null): string
    {
        $day = ($on ? $on->copy() : today())->startOfDay();

        if (! $this->is_active) return 'disabled';
        if ($day->lt($this->start_date)) return 'scheduled';
        if ($day->gt($this->end_date)) return 'expired';

        return 'active';
    }

    public function stateLabel(): string { return self::STATES[$this->state()][0]; }
    public function stateTone(): string  { return self::STATES[$this->state()][1]; }

    /** B2.3 - mã có dùng được vào ngày này không */
    public function isUsableOn(?CarbonInterface $on = null): bool
    {
        return $this->state($on) === 'active';
    }

    /** B2.3 - số tiền được giảm trên một khoản tiền thuê; không bao giờ giảm quá số tiền đó */
    public function discountFor(int $amount): int
    {
        $discount = $this->discount_percent !== null
            ? intdiv($amount * $this->discount_percent, 100)
            : (int) $this->discount_amount;

        return min($discount, $amount);
    }

    public function discountLabel(): string
    {
        return $this->discount_percent !== null
            ? 'Giảm ' . $this->discount_percent . '%'
            : 'Giảm ' . number_format($this->discount_amount) . ' đ';
    }

    /** Lọc danh sách theo trạng thái, khớp đúng với hàm state() ở trên */
    public function scopeInState(Builder $query, string $state): Builder
    {
        $today = today()->toDateString();

        return match ($state) {
            'active'    => $query->where('is_active', true)
                                 ->whereDate('start_date', '<=', $today)
                                 ->whereDate('end_date', '>=', $today),
            'scheduled' => $query->where('is_active', true)->whereDate('start_date', '>', $today),
            'expired'   => $query->where('is_active', true)->whereDate('end_date', '<', $today),
            'disabled'  => $query->where('is_active', false),
            default     => $query,
        };
    }
}
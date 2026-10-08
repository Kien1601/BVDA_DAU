<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id', 'license_plate', 'name', 'brand', 'type', 'image',
        'price_per_hour', 'price_per_day', 'price_per_week', 'status', 'gps_device_id',
    ];

    // Mặc định ẩn dữ liệu GPS khi chuyển sang JSON/array.
    // Trang quản lý sẽ lấy các trường này một cách tường minh qua Resource.
    protected $hidden = ['gps_device_id', 'last_signal_at', 'last_device_time', 'last_lat', 'last_lng'];

    protected function casts(): array
    {
        return [
            'status' => VehicleStatus::class,
            'last_signal_at' => 'datetime',
            'last_device_time' => 'datetime',
            'last_lat' => 'float',
            'last_lng' => 'float',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function gpsLocations(): HasMany
    {
        return $this->hasMany(GpsLocation::class);
    }

    public function latestLocation(): HasOne
    {
        return $this->hasOne(GpsLocation::class)->latestOfMany('device_time');
    }
        /** Danh sách loại xe dùng cho form và kiểm tra dữ liệu */
    public const TYPES = ['Xe số', 'Tay ga', 'Côn tay', 'Xe điện'];

    /**
     * C2.3 - Điều kiện xóa xe.
     * TODO (C5): thêm điều kiện không còn đơn pending/approved/active.
     */
    public function canBeDeleted(): bool
    {
        return $this->status !== VehicleStatus::Rented;
    }

    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($this->image);
    }

        /**
     * Xe khách được xem: bỏ xe đang bảo dưỡng. Xe đã xóa mềm tự bị loại.
     * Xe đang cho thuê vẫn hiện, vì khách có thể đặt cho ngày khác (đặc tả B2).
     */
    public function scopeVisibleToCustomers(Builder $query): Builder
    {
        return $query->where('status', '!=', VehicleStatus::Maintenance->value);
    }
}
<?php

namespace App\Models;

use App\Enums\ViolationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Violation extends Model
{
    protected $fillable = [
        'user_id', 'rental_order_id', 'type', 'description',
        'fine_amount', 'occurred_at', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ViolationType::class,
            'fine_amount' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rentalOrder(): BelongsTo
    {
        return $this->belongsTo(RentalOrder::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
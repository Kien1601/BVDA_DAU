<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'rental_order_id', 'type', 'method', 'amount', 'status', 'txn_ref',
        'provider_txn_id', 'paid_at', 'created_by', 'note', 'payload',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(RentalOrder::class, 'rental_order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
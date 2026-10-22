<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'customer_id', 'customer_lang', 'last_message_at',
        'auto_replied_at', 'staff_read_at', 'customer_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'auto_replied_at' => 'datetime',
            'staff_read_at' => 'datetime',
            'customer_read_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
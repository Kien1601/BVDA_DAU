<?php

namespace App\Models;

use App\Enums\LoginChannel;
use Illuminate\Database\Eloquent\Model;

class LoginCode extends Model
{
    protected $fillable = ['identifier', 'channel', 'code_hash', 'attempts', 'expires_at', 'used_at', 'ip'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'channel' => LoginChannel::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /** Mã còn dùng được: chưa dùng, chưa hết hạn */
    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
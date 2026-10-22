<?php

namespace App\Models;

use App\Enums\SenderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'sender_type', 'sender_id', 'body', 'lang'];

    protected function casts(): array
    {
        return ['sender_type' => SenderType::class];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MessageTranslation::class);
    }

    public function toChatArray(): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_type' => $this->sender_type->value,
            'body' => $this->body,
            'lang' => $this->lang,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
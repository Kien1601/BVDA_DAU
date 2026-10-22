<?php

namespace App\Services;

use App\Enums\SenderType;
use App\Events\ChatMessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class ChatService
{
    private const AUTO_REPLY_DAY = 'Cảm ơn bạn đã liên hệ! Nhân viên sẽ phản hồi bạn trong ít phút.';
    private const AUTO_REPLY_NIGHT = 'Hiện đang ngoài giờ làm việc (7:00 - 21:00). Chúng tôi sẽ phản hồi bạn sớm nhất vào sáng mai.';

    public function __construct(private TranslationService $translator) {}

    public function conversationFor(User $customer): Conversation
    {
        return Conversation::firstOrCreate(['customer_id' => $customer->id]);
    }

    public function sendFromCustomer(User $customer, string $body): Message
    {
        $conversation = $this->conversationFor($customer);
        $lang = $this->translator->detect($body);

        $message = $conversation->messages()->create([
            'sender_type' => SenderType::Customer,
            'sender_id' => $customer->id,
            'body' => $body,
            'lang' => $lang,
        ]);

        $conversation->update([
            'customer_lang' => $lang ?? $conversation->customer_lang,
            'last_message_at' => now(),
            'customer_read_at' => now(),
        ]);

        broadcast(new ChatMessageSent($message));
        $this->autoReply($conversation);

        return $message;
    }

    public function sendFromStaff(Conversation $conversation, User $staff, string $body): Message
    {
        $message = $conversation->messages()->create([
            'sender_type' => SenderType::Staff,
            'sender_id' => $staff->id,
            'body' => $body,
            'lang' => 'vi',
        ]);

        // Nhân viên đã trả lời thì lượt chờ sau được nhận tin tự động lại
        $conversation->update([
            'last_message_at' => now(),
            'staff_read_at' => now(),
            'auto_replied_at' => null,
        ]);

        broadcast(new ChatMessageSent($message));

        return $message;
    }

    // Mỗi lượt chờ chỉ gửi 1 tin tự động, viết sẵn bằng ngôn ngữ của khách
    private function autoReply(Conversation $conversation): void
    {
        if ($conversation->auto_replied_at) {
            return;
        }

        $hour = now()->hour;
        $text = ($hour >= 7 && $hour < 21) ? self::AUTO_REPLY_DAY : self::AUTO_REPLY_NIGHT;
        $lang = $conversation->customer_lang ?? 'vi';
        $body = $lang === 'vi' ? $text : ($this->translator->translate($text, $lang) ?? $text);

        $message = $conversation->messages()->create([
            'sender_type' => SenderType::System,
            'body' => $body,
            'lang' => $body === $text ? 'vi' : $lang,
        ]);

        // Lưu sẵn bản tiếng Việt để nhân viên bật dịch không phải gọi API
        if ($message->lang !== 'vi') {
            $message->translations()->create(['lang' => 'vi', 'body' => $text]);
        }

        $conversation->update(['auto_replied_at' => now()]);
        broadcast(new ChatMessageSent($message));
    }
}
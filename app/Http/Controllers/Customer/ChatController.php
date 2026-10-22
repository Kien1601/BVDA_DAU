<?php

namespace App\Http\Controllers\Customer;

use App\Enums\SenderType;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Services\ChatService;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private ChatService $chat) {}

    // 50 tin gần nhất + số tin chưa đọc
    public function index(Request $request): JsonResponse
    {
        $conversation = $this->chat->conversationFor($request->user());
        $messages = $conversation->messages()->latest('id')->limit(50)->get()->reverse()->values();

        $unread = $conversation->messages()
            ->where('sender_type', '!=', SenderType::Customer->value)
            ->when($conversation->customer_read_at, fn ($q, $at) => $q->where('created_at', '>', $at))
            ->count();

        return response()->json([
            'conversation_id' => $conversation->id,
            'unread' => $unread,
            'messages' => $messages->map->toChatArray(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $message = $this->chat->sendFromCustomer($request->user(), trim($data['body']));

        return response()->json($message->toChatArray(), 201);
    }

    public function read(Request $request): JsonResponse
    {
        $this->chat->conversationFor($request->user())->update(['customer_read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    // Dịch tin của nhân viên sang ngôn ngữ của khách; chỉ trong hội thoại của chính khách
    public function translate(Request $request, TranslationService $translator): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:50'],
            'ids.*' => ['integer'],
            'lang' => ['required', 'regex:/^[a-z]{2}$/'],
        ]);

        $conversation = $this->chat->conversationFor($request->user());

        $result = Message::where('conversation_id', $conversation->id)
            ->whereIn('id', $data['ids'])
            ->get()
            ->mapWithKeys(fn (Message $m) => [$m->id => $translator->forMessage($m, $data['lang'])]);

        return response()->json($result);
    }
}
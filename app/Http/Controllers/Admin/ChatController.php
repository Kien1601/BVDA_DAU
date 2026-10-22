<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    // Danh sách hội thoại đã có tin nhắn, mới nhất lên đầu
       // Danh sách hội thoại đã có tin nhắn, mới nhất lên đầu
    public function index(Request $request): View|JsonResponse
    {
        $conversations = Conversation::query()
            ->whereNotNull('last_message_at')
            ->with(['customer', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get()
            ->map(fn (Conversation $c) => [
                'id' => $c->id,
                'customer_name' => $c->customer?->name ?? 'Khách đã xóa',
                'customer_lang' => $c->customer_lang,
                'last_body' => $c->latestMessage?->body,
                'last_at' => $c->last_message_at?->toIso8601String(),
                // Chưa đọc: khách nhắn sau lần cuối nhân viên mở hội thoại
                'unread' => ! $c->staff_read_at || $c->staff_read_at->lt($c->last_message_at),
            ]);

        if ($request->wantsJson()) {
            return response()->json($conversations);
        }

        return view('admin.chat.index', compact('conversations'));
    }

    // Mở hội thoại thì đánh dấu nhân viên đã đọc
    public function messages(Conversation $conversation): JsonResponse
    {
        $conversation->update(['staff_read_at' => now()]);
        $messages = $conversation->messages()->latest('id')->limit(100)->get()->reverse()->values();

        return response()->json([
            'conversation_id' => $conversation->id,
            'customer_lang' => $conversation->customer_lang,
            'messages' => $messages->map->toChatArray(),
        ]);
    }

    public function store(Request $request, Conversation $conversation, ChatService $chat): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $message = $chat->sendFromStaff($conversation, $request->user(), trim($data['body']));

        return response()->json($message->toChatArray(), 201);
    }

    // Nhân viên luôn đọc bản tiếng Việt
    public function translate(Request $request, TranslationService $translator): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:50'],
            'ids.*' => ['integer'],
        ]);

        $result = Message::whereIn('id', $data['ids'])
            ->get()
            ->mapWithKeys(fn (Message $m) => [$m->id => $translator->forMessage($m, 'vi')]);

        return response()->json($result);
    }
}
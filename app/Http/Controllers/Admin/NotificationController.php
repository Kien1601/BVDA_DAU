<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public const FEED_LIMIT = 10;

    public function feed(Request $request)
    {
        return response()->json(self::payload($request->user()));
    }

    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('admin.dashboard'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    /** Dữ liệu cho chuông: dùng khi render trang và khi gọi lại feed */
    public static function payload(User $user): array
    {
        return [
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->limit(self::FEED_LIMIT)->get()
                ->map(fn (DatabaseNotification $n) => [
                    'id' => $n->id,
                    'title' => $n->data['title'] ?? '',
                    'message' => $n->data['message'] ?? '',
                    'tone' => $n->data['tone'] ?? 'info',
                    'url' => route('admin.notifications.open', $n->id),
                    'time' => $n->created_at->diffForHumans(),
                    'unread' => $n->read_at === null,
                ])->values(),
        ];
    }
}
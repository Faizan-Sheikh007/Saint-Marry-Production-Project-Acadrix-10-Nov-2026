<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Lightweight polling endpoint the topbar bell hits every few seconds. */
    public function poll()
    {
        $userId = auth()->id();

        $unreadCount = Notification::where('user_id', $userId)->where('read', false)->count();

        $latest = Notification::where('user_id', $userId)
            ->latest()
            ->take(8)
            ->get(['id', 'message', 'icon', 'color', 'read', 'created_at']);

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $latest,
        ]);
    }

    public function markRead(Notification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);
        $notification->update(['read' => true]);
        return response()->json(['ok' => true]);
    }

    public function markAllRead()
    {
        Notification::where('user_id', auth()->id())->where('read', false)->update(['read' => true]);
        return response()->json(['ok' => true]);
    }
}

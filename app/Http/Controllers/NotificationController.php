<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->simplePaginate(30);
        $request->user()->unreadNotifications->markAsRead();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Usado pelo app Android: notificações não lidas criadas depois de `since` (timestamp em segundos).
     */
    public function pending(Request $request)
    {
        $since = $request->integer('since');

        $notifications = $request->user()->unreadNotifications()
            ->when($since > 0, fn ($q) => $q->where('created_at', '>', now()->setTimestamp($since)))
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'icon' => $n->data['icon'] ?? '🐾',
                'text' => $n->data['text'] ?? '',
                'url' => $n->data['url'] ?? null,
                'created_at' => $n->created_at->timestamp,
            ]);

        return response()->json([
            'now' => now()->timestamp,
            'unread' => $request->user()->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }
}

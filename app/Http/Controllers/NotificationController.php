<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('notifiable_id', Auth::id())
            ->where('notifiable_type', User::class)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('notifiable_id', Auth::id())
            ->where('notifiable_type', User::class)
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        Notification::where('notifiable_id', Auth::id())
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function getUnreadCount()
    {
        $count = Notification::where('notifiable_id', Auth::id())
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function getRecent()
    {
        $notifications = Notification::where('notifiable_id', Auth::id())
            ->where('notifiable_type', User::class)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json(['notifications' => $notifications]);
    }
}
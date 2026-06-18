<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function unread()
    {
        return response()->json(
            AppNotification::where('user_id', auth()->id())
                ->where('is_read', false)
                ->latest()
                ->get()
        );
    }

    public function markAsRead(AppNotification $notification)
    {
        if ($notification->user_id === auth()->id()) {
            $notification->update(['is_read' => true]);
        }
        return back();
    }

    public function markAllAsRead()
    {
        AppNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        return back();
    }
}

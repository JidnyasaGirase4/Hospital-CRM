<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate($request->integer('per_page', 15));

        return $this->paginated($notifications, NotificationResource::class, 'Notifications retrieved successfully');
    }

    public function markRead(Request $request, string $notification)
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        return $this->success(null, 'Notification marked as read');
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->success(null, 'All notifications marked as read');
    }
}

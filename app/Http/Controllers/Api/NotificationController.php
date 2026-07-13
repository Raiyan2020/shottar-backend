<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationsResource;
use App\Http\Resources\SchoolProductResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
//        $notifications = $user->notifications()
//            ->orderBy('id', 'desc')
//            ->paginate(10);
        $notifications = Notification::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereNull('user_id');
        })
            ->orderBy('id', 'desc')
            ->paginate(10);

        $data = NotificationsResource::collection($notifications);

        return sendResponse([
            'data' => $data,
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'next_page_url' => $notifications->nextPageUrl(),
                'prev_page_url' => $notifications->previousPageUrl(),
            ]
        ], 'Notifications retrieved successfully.');

    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationsResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $hasPaidSubscription = $user->orders()
            ->where('status', 'paid')
            ->exists();

        $notifications = Notification::query()
            ->where(function ($query) use ($user, $hasPaidSubscription) {
                $query->where('user_id', $user->id)
                    ->orWhere(function ($q) {
                        $q->whereNull('user_id')->where('type', 'all');
                    });

                // Unpaid audience notifications: only for users without paid orders.
                if (! $hasPaidSubscription) {
                    $query->orWhere(function ($q) {
                        $q->whereNull('user_id')->where('type', 'unpaid');
                    });
                }
            })
            ->orderByDesc('id')
            ->paginate(10);

        return sendResponse([
            'data' => NotificationsResource::collection($notifications),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'next_page_url' => $notifications->nextPageUrl(),
                'prev_page_url' => $notifications->previousPageUrl(),
            ],
        ], 'Notifications retrieved successfully.');
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::query()
            ->where('id', $id)
            ->where(function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                    ->orWhereNull('user_id');
            })
            ->firstOrFail();

        $notification->is_read = true;
        $notification->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read',
        ]);
    }
}

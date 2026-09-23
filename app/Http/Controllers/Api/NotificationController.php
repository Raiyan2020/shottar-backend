<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationsResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $hasPaidSubscription = $user->orders()
            ->where('status', 'paid')
            ->exists();

        // تبويبات التطبيق (تعليمية/إنجازات/معاملات) بتفلتر على العمود ده.
        // من غير قيمة، أو لو القيمة مش من التصنيفات المعروفة، بيرجع الكل
        // زي ما كان بيحصل قبل ما category يتضاف أصلاً.
        $category = $request->query('category');

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
            ->when(
                is_string($category) && in_array($category, Notification::CATEGORIES, true),
                fn ($query) => $query->where('category', $category)
            )
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

        return sendResponse(null, 'Notification marked as read');
    }
}

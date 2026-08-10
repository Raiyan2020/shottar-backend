<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\NotificationsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\FirebaseNotificationService;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(FirebaseNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(NotificationsDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.notifications.index');
    }

    public function create()
    {
        $users = User::get(['id', 'name', 'phone']);

        return view('dashboard.admin.notifications.create', compact('users'));
    }

    public function store(NotificationRequest $request)
    {
        $data = $request->validated();
        $sendType = $data['send_type']; // all - one - group

        if ($sendType === 'all') {
            $tokens = User::query()
                ->whereNotNull('device_token')
                ->where('device_token', '!=', '')
                ->pluck('device_token')
                ->unique()
                ->values()
                ->all();

            $this->notificationService->sendNotification(
                $tokens,
                $data['title'],
                $data['body'],
                ['type' => 'all']
            );

            Notification::create([
                'user_id' => null,
                'title' => $data['title'],
                'body' => $data['body'],
                'type' => 'all',
            ]);

            return back()->with('success', __('messages.notification_sent'));
        }

        if ($sendType === 'one') {
            $user = User::find($data['user_id'] ?? null);
            $tokens = User::query()
                ->where('id', $data['user_id'] ?? null)
                ->whereNotNull('device_token')
                ->where('device_token', '!=', '')
                ->pluck('device_token')
                ->unique()
                ->values()
                ->all();

            if ($tokens !== []) {
                $this->notificationService->sendNotification(
                    $tokens,
                    $data['title'],
                    $data['body'],
                    ['type' => 'user']
                );
            }

            Notification::create([
                'user_id' => $user?->id,
                'title' => $data['title'],
                'body' => $data['body'],
                'type' => 'user',
            ]);

            return back()->with('success', __('messages.notification_sent'));
        }

        if ($sendType === 'group') {
            $userIds = $data['users'] ?? [];

            $tokens = User::query()
                ->whereIn('id', $userIds)
                ->whereNotNull('device_token')
                ->where('device_token', '!=', '')
                ->pluck('device_token')
                ->unique()
                ->values()
                ->all();

            if ($tokens !== []) {
                $this->notificationService->sendNotification(
                    $tokens,
                    $data['title'],
                    $data['body'],
                    ['type' => 'user']
                );
            }

            foreach ($userIds as $id) {
                Notification::create([
                    'user_id' => $id,
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'type' => 'user',
                ]);
            }

            return back()->with('success', __('messages.notification_sent'));
        }

        return back()->withErrors('Invalid Send Type!');
    }
}

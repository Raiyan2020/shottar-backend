<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\NotificationsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Database\Eloquent\Builder;

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
        $unpaidCount = $this->unpaidUsersQuery()->count();

        return view('dashboard.admin.notifications.create', compact('users', 'unpaidCount'));
    }

    public function store(NotificationRequest $request)
    {
        $data = $request->validated();
        $sendType = $data['send_type']; // all - one - group - unpaid

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

        if ($sendType === 'unpaid') {
            $unpaidUsers = $this->unpaidUsersQuery()->get(['id', 'device_token']);

            $tokens = $unpaidUsers
                ->pluck('device_token')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($tokens !== []) {
                $this->notificationService->sendNotification(
                    $tokens,
                    $data['title'],
                    $data['body'],
                    ['type' => 'unpaid']
                );
            }

            // One broadcast row for unpaid audience (API filters by type).
            Notification::create([
                'user_id' => null,
                'title' => $data['title'],
                'body' => $data['body'],
                'type' => 'unpaid',
            ]);

            return back()->with(
                'success',
                __('messages.notification_sent') . ' (' . $unpaidUsers->count() . ')'
            );
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

    /**
     * Users with no paid subscription/order.
     */
    protected function unpaidUsersQuery(): Builder
    {
        return User::query()
            ->whereDoesntHave('orders', function (Builder $query) {
                $query->where('status', 'paid');
            });
    }
}

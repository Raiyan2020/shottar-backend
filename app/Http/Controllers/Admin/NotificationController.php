<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\NotificationDataTable;
use App\DataTables\NotificationsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Arr;

class NotificationController extends Controller
{

    protected $notificationService;
    function __construct(FirebaseNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(NotificationsDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.notifications.index');

    }
    public function create()
    {
        $users = User::get(['id', 'name','phone']);
//        return $users;
        return view('dashboard.admin.notifications.create', compact('users'));

    }
    public function store(NotificationRequest $request)
    {
        $data = $request->validated();

        $sendType = $data['send_type']; // all - one - group

        // ========= SEND TO ALL ===========
        if ($sendType === 'all') {

//            return $data;
//            $this->notificationService->sendFCMTopic($data);    // اسم التوبك

            $users = User::whereNotNull('device_token')
                ->pluck('device_token')
                ->toArray();
            $this->notificationService->sendNotification($users, $data['title'], $data['body']);

            // تخزين بدون user_id
            Notification::create([
                'user_id' => null,
                'title'   => $data['title'],
                'body'    => $data['body'],
                'type'    => 'all',
            ]);

            return back()->with("success", __("messages.notification_sent"));
        }


        // ========= SEND TO ONE USER ===========
        if ($sendType === 'one') {

            $user = User::where('id', $data['user_id'])->first();
            $device_token = User::where('id', $data['user_id'])
                ->whereNotNull('device_token')
                ->pluck('device_token')
                ->toArray();

            if ($user) {
                // إرسال FCM token واحد
                $this->notificationService->sendNotification($device_token, $data['title'], $data['body']);
            }

            // تخزين
            Notification::create([
                'user_id' => $user?->id,
                'title'   => $data['title'],
                'body'    => $data['body'],
                'type'    => 'user',
            ]);

            return back()->with("success", __("messages.notification_sent"));
        }


        // ========= SEND TO GROUP USERS ===========
        if ($sendType === 'group') {

            $users = User::whereIn('id', $data['users'])
                ->whereNotNull('device_token')
                ->pluck('device_token')
                ->toArray();

            if (!empty($users)) {
                // إرسال إلى مجموعة tokens مرّة وحدة
                $this->notificationService->sendNotification($users, $data['title'], $data['body']);
            }

            // تخزين الإشعار لكل مستخدم
            foreach ($data['users'] as $id) {
                Notification::create([
                    'user_id' => $id,
                    'title'   => $data['title'],
                    'body'    => $data['body'],
                    'type'    => 'user',
                ]);
            }

            return back()->with("success", __("messages.notification_sent"));
        }


        return back()->withErrors("Invalid Send Type!");
    }


    // إرسال إشعار عبر Firebase
}

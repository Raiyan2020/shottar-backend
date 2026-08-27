<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\NotificationsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

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

            $pushed = $this->pushOrWarn($tokens, $data, 'all');

            Notification::create([
                'user_id' => null,
                'title' => $data['title'],
                'body' => $data['body'],
                'type' => 'all',
            ]);

            return $this->sendResult($pushed, __('messages.notification_sent'));
        }

        if ($sendType === 'unpaid') {
            $unpaidUsers = $this->unpaidUsersQuery()->get(['id', 'device_token']);

            $tokens = $unpaidUsers
                ->pluck('device_token')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $pushed = $this->pushOrWarn($tokens, $data, 'unpaid');

            // One broadcast row for unpaid audience (API filters by type).
            Notification::create([
                'user_id' => null,
                'title' => $data['title'],
                'body' => $data['body'],
                'type' => 'unpaid',
            ]);

            return $this->sendResult($pushed, __('messages.notification_sent') . ' (' . $unpaidUsers->count() . ')');
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

            $pushed = $this->pushOrWarn($tokens, $data, 'user');

            Notification::create([
                'user_id' => $user?->id,
                'title' => $data['title'],
                'body' => $data['body'],
                'type' => 'user',
            ]);

            return $this->sendResult($pushed, __('messages.notification_sent'));
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

            $pushed = $this->pushOrWarn($tokens, $data, 'user');

            foreach ($userIds as $id) {
                Notification::create([
                    'user_id' => $id,
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'type' => 'user',
                ]);
            }

            return $this->sendResult($pushed, __('messages.notification_sent'));
        }

        return back()->withErrors('Invalid Send Type!');
    }

    /**
     * بيبعت الإشعار ومبيوقعش الصفحة لو Firebase فشل.
     *
     * قبل كده أي فشل (زي ملف الـ service account الناقص) كان بيطلع صفحة 500
     * للأدمن، والإشعار مكنش بيتسجل أصلاً. دلوقتي بيتسجل في التطبيق على أي حال
     * والأدمن بيشوف رسالة واضحة.
     */
    protected function pushOrWarn(array $tokens, array $data, string $type): bool
    {
        if ($tokens === []) {
            Log::warning('Push notification skipped: audience has no device tokens', [
                'type' => $type,
            ]);

            return false;
        }

        try {
            // استخدم نفس مسار الإرسال المباشر الذي يستعمله أمر
            // notifications:diagnose، حتى لا تعرض لوحة الإدارة نجاحًا بينما
            // الجوب ما زال عالقًا في queue بدون worker.
            $summary = $this->notificationService->sendNotification(
                $tokens,
                $data['title'],
                $data['body'],
                ['type' => $type]
            );

            Log::info('Admin push notification result', [
                'type' => $type,
            ] + $summary);

            return $summary['sent'] > 0 && $summary['failed'] === 0;
        } catch (\Throwable $e) {
            Log::error('Push notification dispatch failed', [
                'type' => $type,
                'tokens' => count($tokens),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * بيرجّع على صفحة القائمة مش back().
     *
     * back() كان بيعيد بناء صفحة الإنشاء، واللي بتحمّل **كل** المستخدمين
     * وبترسم dropdownين بعدد المستخدمين كله + عدّ غير المشتركين. فبعد ما
     * الإرسال نفسه بقى في الخلفية، ده كان لسه بيخلّي الضغط على "إرسال"
     * ياخد وقت طويل — والسبب مكانش الإرسال أصلاً.
     */
    protected function sendResult(bool $pushed, string $successMessage)
    {
        $target = redirect()->route('admin.notifications.index');

        return $pushed
            ? $target->with('success', $successMessage)
            : $target->with('error', __('general.Notification saved, but push delivery failed. Check the Firebase credentials on the server.'));
    }

    /**
     * Users with no paid subscription/order.
     */
    protected function unpaidUsersQuery(): Builder
    {
        return User::query()
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('orders')
                    ->whereColumn('orders.user_id', 'users.id')
                    ->where('orders.status', 'paid');
            });
    }
}

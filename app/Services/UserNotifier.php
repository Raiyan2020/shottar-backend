<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * نقطة موحّدة لإشعار مستخدم واحد ببند (إشعار داخل التطبيق + بوش)، بدل ما كل
 * مكان في الكود يكرر منطق إنشاء الـ Notification والإرسال لـ Firebase.
 * بيستخدمها المشغّلات التلقائية (دفع ناجح، تحقيق الهدف اليومي، درس جديد).
 */
class UserNotifier
{
    public function __construct(private readonly FirebaseNotificationService $push) {}

    public function notify(
        User $user,
        string $category,
        string $titleAr,
        string $bodyAr,
        ?string $titleEn = null,
        ?string $bodyEn = null,
        ?int $orderId = null,
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'title' => $titleAr,
            'title_en' => $titleEn,
            'body' => $bodyAr,
            'body_en' => $bodyEn,
            'type' => 'user',
            'category' => in_array($category, Notification::CATEGORIES, true) ? $category : 'general',
        ]);

        // احترام تفضيل المستخدم بإيقاف البوش — الإشعار برضه بيتسجل في
        // التطبيق (تبويب الإشعارات) حتى لو البوش متوقف، زي أي تطبيق تاني.
        if ($user->notification_enabled && $user->device_token) {
            try {
                $this->push->sendNotification([$user->device_token], $titleAr, $bodyAr, [
                    'type' => 'user',
                    'category' => $notification->category,
                    'notification_id' => $notification->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('UserNotifier push failed', [
                    'user_id' => $user->id,
                    'category' => $category,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notification;
    }
}

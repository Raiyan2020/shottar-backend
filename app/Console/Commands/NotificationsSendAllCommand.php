<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Services\FirebaseService;
use Illuminate\Console\Command;

class NotificationsSendAllCommand extends Command
{
    protected $signature = 'notifications:send-all
                            {--title=اختبار شطار : عنوان الإشعار}
                            {--body=لو وصلتك الرسالة دي يبقى الإشعارات شغالة ✅ : نص الإشعار}
                            {--type=test : قيمة data.type التي يستقبلها التطبيق}
                            {--dry-run : اعرض عدد المستخدمين والتوكنات فقط بدون إرسال}
                            {--force : تأكيد الإرسال الفعلي لكل الأجهزة المسجلة}';

    protected $description = 'إرسال Push Notification مباشرة لكل المستخدمين الذين لديهم device_token صالح ظاهريًا';

    public function handle(
        FirebaseService $firebase,
        FirebaseNotificationService $notifications
    ): int {
        $query = User::query()
            ->whereNotNull('device_token')
            ->where('device_token', '!=', '');

        $usersCount = (clone $query)->count();
        $tokensCount = (clone $query)->distinct()->count('device_token');

        $this->components->twoColumnDetail('مستخدمين عندهم device_token', (string) $usersCount);
        $this->components->twoColumnDetail('توكنات فريدة', (string) $tokensCount);

        if ($tokensCount === 0) {
            $this->components->error('مفيش أي device_token للإرسال. التطبيق لازم يرسل التوكن عند login/register.');

            return self::FAILURE;
        }

        try {
            $firebase->getAccessToken();
            $this->components->twoColumnDetail('Firebase credentials', '<fg=green>شغالة</>');
        } catch (\Throwable $e) {
            $this->components->twoColumnDetail('Firebase credentials', '<fg=red>فاشلة</>');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run فقط — لم يتم إرسال أي إشعار.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->warn('لم يتم الإرسال. أضف --force بعد مراجعة العدد أعلاه.');

            return self::INVALID;
        }

        $title = trim((string) $this->option('title'));
        $body = trim((string) $this->option('body'));

        if ($title === '' || $body === '') {
            $this->components->error('العنوان والنص لا يمكن أن يكونا فارغين.');

            return self::INVALID;
        }

        $total = ['sent' => 0, 'failed' => 0, 'invalid' => 0, 'total' => 0];

        (clone $query)
            ->select(['id', 'device_token'])
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($notifications, $title, $body, &$total) {
                $tokens = $users->pluck('device_token')->filter()->unique()->values()->all();
                $summary = $notifications->sendNotification($tokens, $title, $body, [
                    'type' => (string) $this->option('type'),
                ]);

                foreach (array_keys($total) as $key) {
                    $total[$key] += (int) ($summary[$key] ?? 0);
                }

                $this->line(sprintf(
                    'دفعة: total=%d, sent=%d, failed=%d, invalid=%d',
                    $summary['total'],
                    $summary['sent'],
                    $summary['failed'],
                    $summary['invalid']
                ));
            });

        $this->newLine();
        $this->components->twoColumnDetail('الإجمالي', (string) $total['total']);
        $this->components->twoColumnDetail('تم الإرسال إلى FCM', '<fg=green>'.$total['sent'].'</>');
        $this->components->twoColumnDetail('فشل', $total['failed'] ? '<fg=red>'.$total['failed'].'</>' : '0');
        $this->components->twoColumnDetail('توكنات ميتة تم تنظيفها', (string) $total['invalid']);

        if ($total['failed'] > 0) {
            $this->warn('بعض الرسائل فشلت. راجع storage/logs/laravel.log للتفاصيل.');

            return self::FAILURE;
        }

        $this->components->info('FCM استلم كل الرسائل بنجاح.');

        return self::SUCCESS;
    }
}

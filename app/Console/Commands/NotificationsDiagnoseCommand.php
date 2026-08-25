<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * تشخيص إرسال الإشعارات من على السيرفر.
 *
 * الإشعار بيتسجل في الداتابيز حتى لو الإرسال للأجهزة فشل، فالأدمن بيشوفه في
 * الجدول ويفتكر إنه اتبعت. الأمر ده بيفرّق بين الحالات: مفيش توكن للجهاز،
 * توكن ميت، مشكلة في مفتاح Firebase، ولا الإرسال نجح فعلاً والمشكلة في التطبيق.
 */
class NotificationsDiagnoseCommand extends Command
{
    protected $signature = 'notifications:diagnose
                            {phone? : رقم المستخدم زي ما هو مخزّن، مثال +96560011329}
                            {--send : ابعت إشعار تجريبي فعلي للمستخدم ده}';

    protected $description = 'يتأكد إن إشعارات Firebase بتتبعت فعلاً ويقول سبب الفشل';

    public function handle(): int
    {
        $this->line('');
        $this->components->info('تشخيص إشعارات Firebase');

        $tokenOk = $this->checkFirebase();
        $this->checkTokenCoverage();

        $phone = $this->argument('phone');

        if (! $phone) {
            $this->line('');
            $this->comment('لفحص مستخدم معيّن:  php artisan notifications:diagnose +96560011329 --send');

            return $tokenOk ? self::SUCCESS : self::FAILURE;
        }

        return $this->checkUser($phone, $tokenOk) ? self::SUCCESS : self::FAILURE;
    }

    /** مفتاح Firebase شغال ولا لأ. */
    protected function checkFirebase(): bool
    {
        try {
            app(FirebaseService::class)->getAccessToken();
            $this->components->twoColumnDetail('مفتاح Firebase', '<fg=green>شغال</>');

            return true;
        } catch (\Throwable $e) {
            $this->components->twoColumnDetail('مفتاح Firebase', '<fg=red>فاشل</>');
            $this->line('   <fg=red>'.$e->getMessage().'</>');

            return false;
        }
    }

    /** كام مستخدم عنده device_token أصلاً. */
    protected function checkTokenCoverage(): void
    {
        $total = User::count();
        $withToken = User::whereNotNull('device_token')->where('device_token', '!=', '')->count();

        $this->components->twoColumnDetail('مستخدمين عندهم device_token', $withToken.' من '.$total);

        if ($withToken === 0 && $total > 0) {
            $this->line('   <fg=red>مفيش أي مستخدم عنده توكن — يعني مفيش إشعار بيتبعت لحد.</>');
            $this->line('   <fg=yellow>التطبيق لازم يبعت device_token مع login/register.</>');
        }
    }

    /** فحص مستخدم بعينه، مع إرسال تجريبي اختياري. */
    protected function checkUser(string $phone, bool $firebaseOk): bool
    {
        $user = User::where('phone', $phone)
            ->orWhere('phone', ltrim($phone, '+'))
            ->orWhere('phone', '+'.ltrim($phone, '+'))
            ->first();

        $this->line('');

        if (! $user) {
            $this->components->twoColumnDetail('المستخدم', '<fg=red>مش موجود: '.$phone.'</>');

            return false;
        }

        $this->components->twoColumnDetail('المستخدم', $user->name.' (#'.$user->id.')');

        $token = (string) $user->device_token;

        if ($token === '') {
            $this->components->twoColumnDetail('device_token', '<fg=red>فاضي</>');
            $this->line('   <fg=yellow>ده سبب عدم وصول الإشعار: الباك إند بيتخطى أي مستخدم من غير توكن،</>');
            $this->line('   <fg=yellow>لكنه بيسجّل الإشعار في الداتابيز — عشان كده بيبان إنه اتبعت.</>');
            $this->line('   <fg=yellow>الحل: التطبيق يبعت device_token عند اللوجين، أو المستخدم يعمل لوجين تاني.</>');

            return false;
        }

        $this->components->twoColumnDetail('device_token', substr($token, 0, 24).'... ('.strlen($token).' حرف)');

        if (! $this->option('send')) {
            $this->line('');
            $this->comment('ضيف --send عشان تبعت إشعار تجريبي فعلي.');

            return true;
        }

        if (! $firebaseOk) {
            $this->line('   <fg=red>مش هينفع نبعت — مفتاح Firebase مش شغال.</>');

            return false;
        }

        return $this->sendTest($token);
    }

    /** إرسال مباشر لـ FCM عشان نشوف رد جوجل الخام. */
    protected function sendTest(string $token): bool
    {
        $this->line('');
        $this->components->info('إرسال إشعار تجريبي...');

        $response = Http::withToken(app(FirebaseService::class)->getAccessToken())
            ->timeout(20)
            ->post('https://fcm.googleapis.com/v1/projects/shottar-d93f6/messages:send', [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => 'اختبار شطار',
                        'body' => 'لو وصلتك الرسالة دي يبقى الإشعارات شغالة ✅',
                    ],
                    'android' => ['priority' => 'high', 'notification' => ['sound' => 'default', 'channel_id' => 'default']],
                    'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
                    'data' => ['type' => 'test'],
                ],
            ]);

        if ($response->successful()) {
            $this->components->twoColumnDetail('نتيجة FCM', '<fg=green>اتبعت — '.data_get($response->json(), 'name').'</>');
            $this->line('   <fg=green>جوجل استلم الإشعار. لو مظهرش على الموبايل، المشكلة في التطبيق نفسه</>');
            $this->line('   <fg=green>(صلاحية الإشعارات، أو التطبيق مقفول، أو معالجة الإشعار في Flutter).</>');

            return true;
        }

        $errorStatus = (string) data_get($response->json(), 'error.status');

        $this->components->twoColumnDetail('نتيجة FCM', '<fg=red>فشل ('.$response->status().') '.$errorStatus.'</>');
        $this->line('   '.substr((string) data_get($response->json(), 'error.message'), 0, 200));

        $hint = match ($errorStatus) {
            'UNREGISTERED', 'NOT_FOUND' => 'التوكن ميت — المستخدم شال التطبيق أو التوكن اتغير. لازم يعمل لوجين تاني.',
            'INVALID_ARGUMENT' => 'التوكن شكله غلط أو مش تابع لنفس مشروع Firebase بتاع التطبيق.',
            'SENDER_ID_MISMATCH' => 'التوكن من مشروع Firebase تاني — راجع google-services.json في تطبيق Flutter.',
            'PERMISSION_DENIED' => 'مفتاح الـ service account مالوش صلاحية الإرسال على المشروع ده.',
            'UNAVAILABLE' => 'خدمة FCM مش متاحة دلوقتي — جرّب تاني بعد شوية.',
            default => 'شوف رسالة الخطأ فوق.',
        };

        $this->line('   <fg=yellow>'.$hint.'</>');

        return false;
    }
}

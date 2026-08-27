<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Services\FirebaseService;
use Illuminate\Console\Command;

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

    /** إرسال باستخدام نفس الخدمة التي تستعملها لوحة الإدارة. */
    protected function sendTest(string $token): bool
    {
        $this->line('');
        $this->components->info('إرسال إشعار تجريبي...');

        $summary = app(FirebaseNotificationService::class)->sendNotification(
            [$token],
            'اختبار شطار',
            'لو وصلتك الرسالة دي يبقى الإشعارات شغالة ✅',
            ['type' => 'test'],
        );

        if ($summary['sent'] === 1) {
            $this->components->twoColumnDetail('نتيجة FCM', '<fg=green>اتبعت بنجاح من نفس مسار لوحة الإدارة</>');
            $this->line('   <fg=green>جوجل استلم الإشعار. لو مظهرش على الموبايل، المشكلة في التطبيق نفسه</>');
            $this->line('   <fg=green>(صلاحية الإشعارات، أو التطبيق مقفول، أو معالجة الإشعار في Flutter).</>');

            return true;
        }

        $this->components->twoColumnDetail('نتيجة FCM', '<fg=red>فشل</>');
        $this->line('   <fg=yellow>راجع آخر سطور storage/logs/laravel.log لمعرفة رد Firebase.</>');

        return false;
    }
}

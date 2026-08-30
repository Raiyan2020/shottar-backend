<?php

namespace App\Console\Commands;

use App\Models\PaymentMethod;
use App\Models\Setting;
use Illuminate\Console\Command;

/**
 * فحص جاهزية وضع مراجعة آبل (§9 / is_reviewer).
 *
 *   php artisan apple:check-review-mode
 *   php artisan apple:check-review-mode --fix            # ينشئ/يفعّل apple_iap
 *   php artisan apple:check-review-mode --simulate=1.1.5  # يحاكي النتيجة قبل ما تظبط الحقل
 */
class CheckAppleReviewMode extends Command
{
    protected $signature = 'apple:check-review-mode
                            {--fix : أنشئ أو فعّل وسيلة apple_iap لو ناقصة}
                            {--simulate= : حاكي النتيجة كأن الحقل متظبّط على النسخة دي}';

    protected $description = 'Check Apple App Review mode readiness (apple_iap method + ios_review_version)';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $ok = true;

        // ── 1) وسيلة apple_iap ───────────────────────────────────────────
        $this->line('<comment>1) وسيلة الدفع apple_iap</comment>');
        $iap = PaymentMethod::where('slug', PaymentMethod::SLUG_APPLE_IAP)->first();

        if (! $iap) {
            $ok = false;
            $this->error('   ✘ مش موجودة خالص');

            if ($fix) {
                $iap = PaymentMethod::create([
                    'name_ar' => 'شراء داخل التطبيق (آبل)',
                    'name_en' => 'Apple In-App Purchase',
                    'slug' => PaymentMethod::SLUG_APPLE_IAP,
                    'status' => 1,
                ]);
                $this->info("   ✔ اتعملت (id={$iap->id})");
                $ok = true;
            }
        } elseif (! $iap->status) {
            $this->warn("   ⚠ موجودة (id={$iap->id}) بس **مقفولة**");

            if ($fix) {
                $iap->update(['status' => 1]);
                $this->info('   ✔ اتفعّلت');
            } else {
                $this->line('   ملاحظة: وضع المراجعة بيرجّعها برضه، لكن الأحسن تكون مفعّلة.');
            }
        } else {
            $this->info("   ✔ موجودة ومفعّلة (id={$iap->id})");
        }

        // ── 2) إعداد نسخة المراجعة ───────────────────────────────────────
        $this->newLine();
        $this->line('<comment>2) إعداد ios_review_version</comment>');
        $setting = Setting::where('key_id', 'ios_review_version')->first();

        if (! $setting) {
            $ok = false;
            $this->error('   ✘ الإعداد مش موجود — شغّل php artisan migrate --force');
        } else {
            $value = trim((string) $setting->value);
            $value === ''
                ? $this->line('   ○ فاضي = مفيش نسخة تحت المراجعة (الوضع الطبيعي)')
                : $this->info("   ✔ النسخة تحت المراجعة: {$value}");
        }

        // ── 3) محاكاة حالات الاختبار ─────────────────────────────────────
        $simulate = trim((string) $this->option('simulate'));

        if ($simulate !== '') {
            $original = $setting?->value;
            $setting?->update(['value' => $simulate]);
            $this->newLine();
            $this->line("<comment>3) محاكاة والحقل = {$simulate}</comment>");
        } else {
            $this->newLine();
            $this->line('<comment>3) النتيجة بالإعداد الحالي</comment>');
        }

        $current = trim((string) Setting::where('key_id', 'ios_review_version')->value('value'));

        $rows = [];
        foreach ([
            ['ios', $current !== '' ? $current : '1.1.5', 'النسخة تحت المراجعة'],
            ['ios', '1.1.4', 'نسخة إنتاج على iOS'],
            ['android', $current !== '' ? $current : '1.1.5', 'أندرويد بنفس النسخة'],
            [null, null, 'بيلد قديم بدون هيدرز'],
        ] as [$device, $version, $label]) {
            $isReviewer = app_in_apple_review($device, $version);

            $methods = $isReviewer
                ? PaymentMethod::where('slug', PaymentMethod::SLUG_APPLE_IAP)->pluck('slug')
                : PaymentMethod::where('status', 1)->pluck('slug');

            $rows[] = [
                $label,
                $device ?? '—',
                $version ?? '—',
                $isReviewer ? 'true' : 'false',
                $methods->implode(', ') ?: '(فاضي!)',
            ];
        }

        $this->table(['الحالة', 'device-type', 'app-version', 'is_reviewer', 'الوسائل'], $rows);

        if ($simulate !== '') {
            $setting?->update(['value' => $original]);
            $this->line('  (المحاكاة انتهت — الإعداد رجع زي ما كان)');
        }

        $this->newLine();

        if (! $ok) {
            $this->error('فيه حاجة ناقصة — شغّل: php artisan apple:check-review-mode --fix');

            return self::FAILURE;
        }

        $this->info('جاهز. قبل ما تبعت البيلد لآبل: حط رقم النسخة في "إصدار iOS تحت مراجعة آبل" من الإعدادات.');

        return self::SUCCESS;
    }
}

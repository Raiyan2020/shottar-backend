<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

/**
 * §9 — تشخيص وإصلاح نصوص الشروط وسياسة الخصوصية.
 *
 * النص المخزّن حاليًا في الإنتاج فيه 0 أسطر (`\n`)، والفواصل اتحوّلت لمسافات
 * مزدوجة و `\t•\t`، وفيه `]` زايدة في أول النص. الأمر ده:
 *
 *   php artisan settings:fix-legal            # تشخيص بس (مش بيكتب حاجة)
 *   php artisan settings:fix-legal --fix      # بيصلح فعلًا
 *
 * ملاحظة: بعد ما الخانة في لوحة التحكم بقت textarea، أي حفظ جديد بيحافظ على
 * الأسطر لوحده — الأمر ده لإصلاح النص القديم مرة واحدة.
 */
class FixLegalSettingsText extends Command
{
    protected $signature = 'settings:fix-legal
                            {--fix : اكتب التعديلات فعلًا في الداتابيز}';

    protected $description 
        = 'Diagnose and repair the terms / privacy policy settings text (line breaks, stray brackets, duplicates)';

    protected array $keys = ['terms_ar', 'terms_en', 'privacy_policy_ar', 'privacy_policy_en'];

    public function handle(): int
    {
        $apply = (bool) $this->option('fix');

        $this->info($apply ? '== إصلاح نصوص الشروط والخصوصية ==' : '== تشخيص نصوص الشروط والخصوصية (dry-run) ==');
        $this->newLine();

        $values = [];
        $rows = [];

        foreach ($this->keys as $key) {
            $original = (string) (setting($key) ?? '');
            $fixed = $this->normalize($original);
            $values[$key] = ['original' => $original, 'fixed' => $fixed];

            $rows[] = [
                $key,
                mb_strlen($original),
                substr_count($original, "\n"),
                mb_strlen($fixed),
                substr_count($fixed, "\n"),
                $original === $fixed ? '-' : 'سيتم التعديل',
            ];
        }

        $this->table(
            ['key', 'حروف (قبل)', 'أسطر (قبل)', 'حروف (بعد)', 'أسطر (بعد)', 'الحالة'],
            $rows
        );

        // §9.2 — الشروط وسياسة الخصوصية لازم يكونوا نصين مختلفين
        foreach (['ar', 'en'] as $lang) {
            $terms = $values["terms_{$lang}"]['fixed'] ?? '';
            $privacy = $values["privacy_policy_{$lang}"]['fixed'] ?? '';

            if ($terms !== '' && $terms === $privacy) {
                $this->warn("⚠  terms_{$lang} و privacy_policy_{$lang} نفس النص بالحرف — لازم حد يدخل المحتوى الصح من لوحة التحكم (الأمر ده مش بيقدر يخترع نص).");
            }
        }

        // §9.5 — النسخة الإنجليزية
        foreach (['terms', 'privacy_policy'] as $base) {
            $ar = $values["{$base}_ar"]['fixed'] ?? '';
            $en = $values["{$base}_en"]['fixed'] ?? '';

            if ($en === '') {
                $this->warn("⚠  {$base}_en فاضي — الـ API بيرجّع العربي بدلًا منه.");
            } elseif ($en === $ar) {
                $this->warn("⚠  {$base}_en نفس النص العربي — محتاج ترجمة فعلية.");
            }
        }

        $this->newLine();

        if (! $apply) {
            $changed = collect($values)->filter(fn ($v) => $v['original'] !== $v['fixed'])->keys();

            if ($changed->isEmpty()) {
                $this->info('مفيش حاجة محتاجة تعديل تلقائي.');
            } else {
                $this->info('للتنفيذ الفعلي: php artisan settings:fix-legal --fix');
                $this->line('الحقول اللي هتتعدّل: ' . $changed->implode(', '));
            }

            return self::SUCCESS;
        }

        $updated = 0;

        foreach ($values as $key => $pair) {
            if ($pair['original'] === $pair['fixed']) {
                continue;
            }

            Setting::where('key_id', $key)->update(['value' => $pair['fixed']]);
            $this->line("✔ تم تحديث {$key}");
            $updated++;
        }

        $this->info($updated ? "تم تحديث {$updated} حقل." : 'مفيش حاجة اتغيّرت.');

        return self::SUCCESS;
    }

    /**
     * بيرجّع النص بأسطر حقيقية من غير ما يغيّر أي كلمة.
     */
    protected function normalize(string $text): string
    {
        if (trim($text) === '') {
            return $text;
        }

        // 1) توحيد نهايات الأسطر
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // 2) §9.3 — شيل الـ `]` الزايدة في أول النص (زي "… منصة شطار ]")
        $text = preg_replace('/^\s*\]\s*/u', '', $text);
        $text = preg_replace('/(منصة شطار|Shottar)\s*\]/u', '$1', $text);

        // 3) لو النص أصلًا فيه أسطر، منلمسش التنسيق تاني (idempotent)
        if (substr_count($text, "\n") > 0) {
            return trim($text);
        }

        // 4) النقاط: `\t•\t` أو ` • ` → سطر جديد يبدأ بـ •
        $text = preg_replace('/[ \t]*•[ \t]*/u', "\n• ", $text);

        // 5) العناوين المرقّمة: `1)` … `99)` → سطر فاضي قبلها
        $text = preg_replace('/[ \t]*(?<![\d])(\d{1,2}\))[ \t]*/u', "\n\n$1 ", $text);

        // 6) فواصل الفقرات: مسافتين أو أكتر → سطر جديد
        $text = preg_replace('/[ \t]{2,}/u', "\n", $text);

        // 7) تنضيف: مسافات آخر السطر + أكتر من سطرين فاضيين
        $text = preg_replace('/[ \t]+\n/u', "\n", $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);

        return trim($text);
    }
}

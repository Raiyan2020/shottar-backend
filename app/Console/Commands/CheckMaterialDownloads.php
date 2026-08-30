<?php

namespace App\Console\Commands;

use App\Models\CourseMaterial;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * فحص سريع: هل تحميل الملفات شغّال؟
 *
 *   php artisan materials:check-downloads
 *
 * بيفرّق بين حاجتين الناس بتخلط بينهم:
 *   - آلية التحميل مكسورة (مشكلة كود)
 *   - الملفات نفسها مش موجودة (مشكلة بيانات — محتاجة باك أب)
 */
class CheckMaterialDownloads extends Command
{
    protected $signature = 'materials:check-downloads {--token : اعمل توكن اختبار واطبع أمر curl جاهز}';

    protected $description = 'Check whether note/exam downloads work, and print a ready test command';

    public function handle(): int
    {
        $withFile = fn ($q) => $q->whereNotNull('file')->where('file', '!=', '');

        $notes = $withFile(CourseMaterial::query()->where('type', 'note'))->get();
        $exams = $withFile(Exam::query())->get();

        $present = [];
        $missing = 0;

        foreach ([['note', $notes], ['exam', $exams]] as [$type, $rows]) {
            foreach ($rows as $r) {
                $rel = ltrim(preg_replace('#^storage/#', '', (string) normalize_public_path($r->file)), '/');

                if ($rel && Storage::disk('public')->exists($rel)) {
                    $present[] = ['type' => $type, 'row' => $r, 'size' => Storage::disk('public')->size($rel)];
                } else {
                    $missing++;
                }
            }
        }

        $total = count($present) + $missing;

        $this->newLine();
        $this->table(['الحالة', 'العدد'], [
            ['ملفها موجود — التحميل المفروض يشتغل', count($present)],
            ['ملفها مفقود — هترجع 404', $missing],
            ['الإجمالي', $total],
        ]);

        if ($present === []) {
            $this->error('مفيش ولا ملف واحد موجود على القرص — مش هينفع نختبر التحميل قبل ما ترجّع الباك أب.');

            return self::FAILURE;
        }

        // نفضّل مذكرة مجانية عشان التست ميحتاجش اشتراك
        usort($present, fn ($a, $b) => ($b['row']->is_free ? 1 : 0) <=> ($a['row']->is_free ? 1 : 0));
        $pick = $present[0];
        $row = $pick['row'];

        $this->newLine();
        $this->line('<comment>ملف شغّال للاختبار:</comment>');
        $this->line("  [{$pick['type']} #{$row->id}] " . ($row->name_ar ?: $row->name_en));
        $this->line('  الحجم: ' . number_format($pick['size'] / 1024, 1) . ' KB');
        $this->line('  ' . ($row->is_free ? 'مجاني — مش محتاج اشتراك' : '⚠️ مدفوع — التوكن لازم يكون لمستخدم مشترك في المادة دي'));

        $url = url("/api/material-file/{$pick['type']}/{$row->id}");
        $this->newLine();

        if ($this->option('token')) {
            $user = User::whereNotNull('phone')->first();

            if (! $user) {
                $this->warn('مفيش مستخدمين — مش هينفع نعمل توكن.');

                return self::SUCCESS;
            }

            $token = $user->createToken('download-check')->plainTextToken;
            $this->line('<comment>شغّل الأمر ده:</comment>');
            $this->newLine();
            $this->line("curl -s -o /tmp/test.pdf -D - \\");
            $this->line("  -H 'Authorization: Bearer {$token}' \\");
            $this->line("  '{$url}' | head -12");
            $this->newLine();
            $this->line('  ثم:  file /tmp/test.pdf     ← المفروض يقول PDF document');
            $this->newLine();
            $this->warn('التوكن ده لمستخدم حقيقي — امسحه بعد التست:');
            $this->line("  php artisan tinker --execute='\\Laravel\\Sanctum\\PersonalAccessToken::where(\"name\",\"download-check\")->delete();'");
        } else {
            $this->line('<comment>المسار للاختبار:</comment> ' . $url);
            $this->line('  محتاج Authorization: Bearer <token>');
            $this->newLine();
            $this->line('  لأمر curl جاهز بتوكن: php artisan materials:check-downloads --token');
        }

        return self::SUCCESS;
    }
}

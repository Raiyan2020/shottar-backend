<?php

namespace App\Console\Commands;

use App\Models\CourseMaterial;
use App\Models\Exam;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * فحص ملفات المذكرات والاختبارات على القرص.
 *
 *   php artisan files:audit-materials            # تقرير بس
 *   php artisan files:audit-materials --fix      # يرجّع اللي لقاه في مكان تاني
 *   php artisan files:audit-materials --csv=out.csv
 *
 * الملف ممكن يكون موجود بس في مكان غير اللي الـ API بيدوّر فيه (بسبب نقل
 * السيرفر أو اختلاف مسارات التخزين). الأمر بيدوّر بالاسم في كل الأماكن
 * المحتملة قبل ما يقول إنه ضايع فعلًا.
 */
class AuditMaterialFiles extends Command
{
    protected $signature = 'files:audit-materials
                            {--fix : انسخ الملف للمكان الصح لو اتلقى في مكان تاني}
                            {--csv= : احفظ السجلات الناقصة في ملف CSV}
                            {--root=* : أماكن إضافية يدوّر فيها (مثال: --root=~/public_html)}';

    protected $description = 'Audit note/exam files on disk and recover ones that moved';

    /** @var array<string, string> basename => absolute path */
    protected array $index = [];

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');

        $this->info('بناء فهرس الملفات الموجودة على القرص…');
        $this->buildIndex();
        $this->line('  ملفات مفهرسة: ' . count($this->index));
        $this->newLine();

        $records = $this->collectRecords();

        if ($records === []) {
            $this->info('مفيش مذكرات أو اختبارات فيها ملفات.');

            return self::SUCCESS;
        }

        $this->info(count($records) . ' سجل بيتفحص…');
        $bar = $this->output->createProgressBar(count($records));
        $bar->start();

        $ok = 0;
        $present = [];
        $recovered = 0;
        $recoverable = [];
        $missing = [];

        foreach ($records as $rec) {
            $bar->advance();

            $relative = $this->relativePath($rec['file']);

            if ($relative === null) {
                continue; // رابط خارجي — مش ملف عندنا
            }

            if (Storage::disk('public')->exists($relative)) {
                $ok++;
                $present[] = $rec;
                continue;
            }

            // مش في المكان الصح — ندوّر بالاسم
            $found = $this->index[strtolower(basename($relative))] ?? null;

            if ($found !== null) {
                if ($fix) {
                    Storage::disk('public')->put($relative, file_get_contents($found));
                    $recovered++;
                } else {
                    $recoverable[] = $rec + ['found_at' => $found];
                }

                continue;
            }

            $missing[] = $rec + ['expected' => Storage::disk('public')->path($relative)];
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['النتيجة', 'العدد'], [
            ['موجود في مكانه الصح', $ok],
            [$fix ? 'اترجّع' : 'ممكن يترجّع (في مكان تاني)', $fix ? $recovered : count($recoverable)],
            ['ضايع فعلًا — محتاج رفع من جديد', count($missing)],
        ]);

        if ($recoverable !== []) {
            $this->newLine();
            $this->warn('ملفات موجودة بس في مكان غلط (أول 10):');
            foreach (array_slice($recoverable, 0, 10) as $r) {
                $this->line("  [{$r['type']} #{$r['id']}] {$r['file']}");
                $this->line("      اتلقى في: {$r['found_at']}");
            }
            $this->newLine();
            $this->info('لاسترجاعها: php artisan files:audit-materials --fix');
        }

        if ($missing !== [] && $present !== []) {
            $this->newLine();
            $this->line('<comment>تواريخ الرفع — بتحدد الباك أب اللي محتاجه:</comment>');
            $this->table(
                ['المجموعة', 'العدد', 'أقدم رفع', 'أحدث رفع'],
                [
                    ['موجودة', count($present), $this->minDate($present), $this->maxDate($present)],
                    ['ضايعة', count($missing), $this->minDate($missing), $this->maxDate($missing)],
                ]
            );

            $latestMissing = $this->maxDate($missing);
            if ($latestMissing !== '-') {
                $this->line("  → دوّر على باك أب متاخد <options=bold>بعد {$latestMissing}</> عشان يكون شامل كل الملفات الضايعة.");
            }
        }

        if ($missing !== []) {
            $this->newLine();
            $this->error('ملفات مش موجودة على القرص خالص (أول 15):');
            foreach (array_slice($missing, 0, 15) as $r) {
                $this->line("  [{$r['type']} #{$r['id']}] {$r['subject']} — {$r['title']}");
                $this->line("      المسار المسجّل: {$r['file']}");
            }

            if ($path = $this->option('csv')) {
                $this->writeCsv($path, $missing);
                $this->info("القائمة الكاملة اتحفظت في: {$path}");
            } else {
                $this->newLine();
                $this->info('للقائمة الكاملة: php artisan files:audit-materials --csv=missing-files.csv');
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{type:string,id:int,title:string,subject:string,file:string}>
     */
    protected function collectRecords(): array
    {
        $records = [];

        CourseMaterial::query()
            ->where('type', 'note')
            ->whereNotNull('file')->where('file', '!=', '')
            ->with('subject:id,name_ar,name_en')
            ->chunkById(200, function ($rows) use (&$records) {
                foreach ($rows as $r) {
                    $records[] = [
                        'type' => 'note',
                        'id' => $r->id,
                        'title' => $r->name_ar ?: $r->name_en ?: '-',
                        'subject' => optional($r->subject)->name_ar ?? ('#' . $r->subject_id),
                        'file' => (string) $r->file,
                        'created_at' => optional($r->created_at)->toDateString(),
                    ];
                }
            });

        Exam::query()
            ->whereNotNull('file')->where('file', '!=', '')
            ->with('subject:id,name_ar,name_en')
            ->chunkById(200, function ($rows) use (&$records) {
                foreach ($rows as $r) {
                    $records[] = [
                        'type' => 'exam',
                        'id' => $r->id,
                        'title' => $r->name_ar ?: $r->name_en ?: '-',
                        'subject' => optional($r->subject)->name_ar ?? ('#' . $r->subject_id),
                        'file' => (string) $r->file,
                        'created_at' => optional($r->created_at)->toDateString(),
                    ];
                }
            });

        return $records;
    }

    protected function relativePath(string $stored): ?string
    {
        $path = normalize_public_path($stored);

        if ($path === null || preg_match('#^https?://#i', $path)) {
            return null;
        }

        return ltrim(preg_replace('#^storage/#', '', $path), '/');
    }

    /**
     * فهرس basename => مسار، من كل الأماكن اللي ممكن الملفات تكون فيها.
     */
    protected function buildIndex(): void
    {
        $extra = [];

        foreach ((array) $this->option('root') as $dir) {
            // ندعم ~ عشان سهولة الكتابة
            $dir = preg_replace('#^~#', rtrim((string) getenv('HOME'), '/'), (string) $dir);
            $real = realpath($dir);

            if ($real && is_dir($real)) {
                $extra[] = $real;
            } else {
                $this->warn("مسار مش موجود، اتتجاهل: {$dir}");
            }
        }

        $roots = array_unique(array_filter(array_merge([
            storage_path('app/public'),
            public_path(),
            storage_path('app'),
        ], $extra), 'is_dir'));

        foreach ($roots as $root) {
            $this->line('  بيدوّر في: ' . $root);
        }

        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
                // مجلدات ممنوعة الوصول متوقّفش المسح
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                // بنهتم بالملفات القابلة للتنزيل بس
                if (! preg_match('/\.(pdf|docx?|pptx?|xlsx?|zip)$/i', $file->getFilename())) {
                    continue;
                }

                $key = strtolower($file->getFilename());
                $this->index[$key] ??= $file->getPathname();
            }
        }
    }

    protected function minDate(array $rows): string
    {
        $dates = array_filter(array_column($rows, 'created_at'));

        return $dates === [] ? '-' : min($dates);
    }

    protected function maxDate(array $rows): string
    {
        $dates = array_filter(array_column($rows, 'created_at'));

        return $dates === [] ? '-' : max($dates);
    }

    protected function writeCsv(string $path, array $missing): void
    {
        $fh = fopen($path, 'w');
        fwrite($fh, "\xEF\xBB\xBF"); // BOM عشان إكسل يقرأ العربي
        fputcsv($fh, ['type', 'id', 'subject', 'title', 'stored_path', 'uploaded_at']);

        foreach ($missing as $r) {
            fputcsv($fh, [$r['type'], $r['id'], $r['subject'], $r['title'], $r['file'], $r['created_at'] ?? '']);
        }

        fclose($fh);
    }
}

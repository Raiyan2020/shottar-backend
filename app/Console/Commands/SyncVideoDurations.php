<?php

namespace App\Console\Commands;

use App\Models\CourseMaterial;
use App\Models\Subject;
use App\Services\VimeoService;
use Illuminate\Console\Command;

/**
 * تعبئة مدة الدروس من Vimeo للدروس القديمة.
 *
 *   php artisan videos:sync-durations --dry-run   # تشخيص من غير كتابة
 *   php artisan videos:sync-durations             # تعبئة الناقص فقط
 *   php artisan videos:sync-durations --all       # إعادة فحص كل الدروس
 */
class SyncVideoDurations extends Command
{
    protected $signature = 'videos:sync-durations
                            {--dry-run : اعرض اللي هيتغيّر من غير ما تكتب}
                            {--all : افحص كل الدروس مش الناقصة بس}
                            {--subject= : رقم مادة واحدة بس}';

    protected $description = 'Fetch real video durations from Vimeo and store them on lessons';

    public function handle(VimeoService $vimeo): int
    {
        $dry = (bool) $this->option('dry-run');

        $query = CourseMaterial::query()
            ->where('type', 'lesson')
            ->whereNotNull('video')
            ->where('video', '!=', '');

        if (! $this->option('all')) {
            // الناقص = null أو 0
            $query->where(function ($q) {
                $q->whereNull('duration')->orWhere('duration', 0)->orWhere('duration', '');
            });
        }

        if ($subjectId = $this->option('subject')) {
            $query->where('subject_id', (int) $subjectId);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('مفيش دروس محتاجة تحديث.');

            return self::SUCCESS;
        }

        $this->info(($dry ? 'تشخيص' : 'تحديث') . " {$total} درس…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $pending = 0;
        $skipped = 0;
        $touchedSubjects = [];

        $query->orderBy('id')->chunkById(100, function ($materials) use (
            $vimeo, $dry, $bar, &$updated, &$pending, &$skipped, &$touchedSubjects
        ) {
            foreach ($materials as $material) {
                $bar->advance();

                if (! VimeoService::extractVideoId($material->video)) {
                    $skipped++;   // مش رابط Vimeo
                    continue;
                }

                $duration = $vimeo->fetchDuration($material->video);

                if ($duration === null) {
                    $pending++;   // لسه بيترمّز أو الـ API رفض
                    continue;
                }

                if ((int) $material->duration === $duration) {
                    continue;     // متطابقة بالفعل
                }

                if (! $dry) {
                    $material->update([
                        'duration' => $duration,
                        'duration_text' => gmdate('H:i:s', $duration),
                    ]);
                }

                $touchedSubjects[$material->subject_id] = true;
                $updated++;

                // تهدئة بسيطة لتجنّب rate limit من Vimeo
                usleep(120000);
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(['النتيجة', 'العدد'], [
            [$dry ? 'هيتحدّث' : 'اتحدّث', $updated],
            ['لسه بيترمّز / تعذّر الجلب', $pending],
            ['مش روابط Vimeo', $skipped],
        ]);

        if ($touchedSubjects !== []) {
            $this->line('مواد اتأثرت: ' . count($touchedSubjects));

            if (! $dry) {
                $this->showSubjectTotals(array_keys($touchedSubjects));
            }
        }

        if ($pending > 0) {
            $this->warn("فيه {$pending} فيديو لسه المدة بتاعته مش جاهزة على Vimeo — شغّل الأمر تاني بعدين.");
        }

        if ($dry) {
            $this->info('ده كان dry-run. للتنفيذ: php artisan videos:sync-durations');
        }

        return self::SUCCESS;
    }

    protected function showSubjectTotals(array $subjectIds): void
    {
        $rows = [];

        foreach (Subject::whereIn('id', array_slice($subjectIds, 0, 15))->get() as $subject) {
            $seconds = (int) $subject->courseMaterials()
                ->where('type', 'lesson')
                ->where('status', 1)
                ->sum('duration');

            $rows[] = [
                $subject->id,
                mb_strimwidth($subject->name_ar ?? $subject->name_en ?? '-', 0, 28, '…'),
                $seconds,
                floor($seconds / 3600) . 'س ' . floor(($seconds % 3600) / 60) . 'د',
            ];
        }

        $this->newLine();
        $this->line('إجمالي المدة بعد التحديث:');
        $this->table(['#', 'المادة', 'ثواني', 'الإجمالي'], $rows);
    }
}

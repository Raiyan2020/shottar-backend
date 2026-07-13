<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WeeklyTeacherVideosReportService
{
    private const REPORTS_TABLE = 'weekly_video_reports_tables';

    /**
     * Generate report for previous week.
     *
     * @return array{report: object, rows: array<int, array<string,mixed>>, big_threshold: int, message: string}
     */
    public function generateForLastWeek(): array
    {
        $start = Carbon::now()->subWeek()->startOfWeek()->toDateString();
        $end = Carbon::now()->subWeek()->endOfWeek()->toDateString();

        return $this->generateForPeriod($start, $end);
    }

    /**
     * @param  string  $startDate  e.g. 2026-03-18
     * @param  string  $endDate    e.g. 2026-03-24
     */
    public function generateForPeriod(string $startDate, string $endDate): array
    {
        return DB::transaction(function () use ($startDate, $endDate) {
            $bigThreshold = (int) (setting('weekly_report_big_upload_threshold', 5));
            if ($bigThreshold <= 0) {
                $bigThreshold = 5;
            }

            // منع تكرار نفس التقرير (نستخدم الجدول الموجود من migration الحالي)
            $report = DB::table(self::REPORTS_TABLE)
                ->where('period_start', $startDate)
                ->where('period_end', $endDate)
                ->first();

            // دائما نحسب الأرقام حتى لو التقرير موجود (بس بنمنع إعادة الإرسال بعد "sent")
            $teacherIds = DB::table('teacher_subjects')
                ->distinct()
                ->pluck('teacher_id')
                ->filter()
                ->values();

            $rows = [];
            if ($teacherIds->isNotEmpty()) {
                $rowsResult = DB::table('admins as a')
                    ->whereIn('a.id', $teacherIds)
                    ->leftJoin('course_materials as cm', function ($join) {
                        $join->on('cm.uploaded_by', '=', 'a.id')
                            ->where('cm.type', '=', 'lesson')
                            ->whereNotNull('cm.video')
                            ->where('cm.video', '<>', '');
                    })
                    ->selectRaw(
                        "
                        a.id as teacher_id,
                        a.name as teacher_name,
                        SUM(CASE WHEN cm.created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as week_videos,
                        COUNT(cm.id) as total_videos
                        ",
                        [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
                    )
                    ->groupBy('a.id', 'a.name')
                    ->orderByDesc('week_videos')
                    ->get();

                $rows = $rowsResult->map(static function ($r) {
                    return [
                        'teacher_id' => (int) $r->teacher_id,
                        'teacher_name' => $r->teacher_name,
                        'week_videos' => (int) $r->week_videos,
                        'total_videos' => (int) $r->total_videos,
                    ];
                })->all();
            }

            $bigTeachers = array_values(array_filter(
                $rows,
                static fn ($r) => ($r['week_videos'] ?? 0) >= $bigThreshold
            ));

            $bigVideosTotal = array_sum(array_map(static fn ($r) => (int) ($r['week_videos'] ?? 0), $bigTeachers));
            $bigTeachersCount = count($bigTeachers);

            $message = $this->buildArabicMessage(
                startDate: $startDate,
                endDate: $endDate,
                rows: $rows,
                bigThreshold: $bigThreshold,
                bigTeachersCount: $bigTeachersCount,
                bigVideosTotal: $bigVideosTotal
            );

            if ($report) {
                // إذا التقرير "sent" نتركه (بس نخزن الرسالة المحدثة احتياطاً)
                DB::table(self::REPORTS_TABLE)
                    ->where('id', $report->id)
                    ->update([
                        'generated_at' => now(),
                        'message' => $message,
                        'status' => $report->status === 'sent' ? 'sent' : 'pending',
                        'updated_at' => now(),
                    ]);

                $report->message = $message;
            } else {
                $reportId = DB::table(self::REPORTS_TABLE)->insertGetId([
                    'period_start' => $startDate,
                    'period_end' => $endDate,
                    'generated_at' => now(),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'message' => $message,
                ]);

                $report = DB::table(self::REPORTS_TABLE)->where('id', $reportId)->first();
            }

            return [
                'report' => $report,
                'rows' => $rows,
                'big_threshold' => $bigThreshold,
                'message' => $message,
            ];
        });
    }

    /**
     * @param array<int, array<string,mixed>> $rows
     */
    private function buildArabicMessage(
        string $startDate,
        string $endDate,
        array $rows,
        int $bigThreshold,
        int $bigTeachersCount,
        int $bigVideosTotal
    ): string {
        $lines = [];

        $lines[] = 'تقرير أسبوعي: فيديوهات الدروس (شطار)';
        $lines[] = "الفترة: من {$startDate} إلى {$endDate}";
        $lines[] = str_repeat('-', 55);

        if (count($rows) === 0) {
            $lines[] = 'لا توجد بيانات لهذا الأسبوع.';
        } else {
            $lines[] = 'عدد الفيديوهات لكل مدرس:';
            foreach ($rows as $r) {
                $teacherName = (string) ($r['teacher_name'] ?? '-');
                $weekVideos = (int) ($r['week_videos'] ?? 0);
                $totalVideos = (int) ($r['total_videos'] ?? 0);

                $lines[] = "• {$teacherName}: هذا الأسبوع = {$weekVideos} ، الإجمالي = {$totalVideos}";
            }
        }

        $lines[] = str_repeat('-', 55);
        $lines[] = "الرفع الكبير (>= {$bigThreshold} فيديو/أسبوع):";
        $lines[] = "عدد المدرسين الذين حققوا الرفع الكبير = {$bigTeachersCount}";
        $lines[] = "إجمالي فيديوهات الرفع الكبير = {$bigVideosTotal}";

        // قائمة المميزين
        $bigRows = array_values(array_filter(
            $rows,
            static fn ($r) => (int) ($r['week_videos'] ?? 0) >= $bigThreshold
        ));

        if (count($bigRows) > 0) {
            $lines[] = 'قائمة المدرسين (الرفع الكبير):';
            foreach ($bigRows as $r) {
                $lines[] = "• " . ($r['teacher_name'] ?? '-') . ': ' . (int) ($r['week_videos'] ?? 0) . ' فيديو';
            }
        } else {
            $lines[] = 'لا يوجد مدرس حقق شرط الرفع الكبير هذا الأسبوع.';
        }

        $lines[] = str_repeat('-', 55);
        $lines[] = 'تم التوليد تلقائياً يوم ' . now()->format('Y-m-d H:i');

        return implode("\n", $lines);
    }
}


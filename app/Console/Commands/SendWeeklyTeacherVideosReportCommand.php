<?php

namespace App\Console\Commands;

use App\Helpers\Functions;
use App\Services\WeeklyTeacherVideosReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendWeeklyTeacherVideosReportCommand extends Command
{
    use Functions;

    protected $signature = 'reports:weekly-teachers-videos {--dry-run : Do not send WhatsApp message} {--force : Send again even if report was already sent}';

    protected $description = 'Send weekly teacher videos report on WhatsApp';

    public function handle(WeeklyTeacherVideosReportService $service): int
    {
        $result = $service->generateForLastWeek();
        $report = $result['report'];
        $message = $result['message'];

        // إذا كان تم الإرسال سابقاً لن نرسل مرة ثانية
        $force = (bool) $this->option('force');
        if (!empty($report->status) && $report->status === 'sent' && !$force) {
            $this->info('Weekly report already sent. Skipping.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        // ارسل التقرير لرقم واحد فقط (الادمن)
        $adminPhoneRaw = setting('weekly_report_admin_whatsapp_phone', setting('phone'));
        $phones = $this->normalizePhones($adminPhoneRaw);
        $phone = $phones[0] ?? null;

        if (empty($phone)) {
            $this->error('Admin WhatsApp phone is missing. Set setting(`weekly_report_admin_whatsapp_phone`) or `phone`.');
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('Dry-run enabled. Message preview below:');
            $this->line('----------------------------------------');
            $this->line($message);
            $this->line('----------------------------------------');
            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            // لو كان التقرير "sent" سابقاً لكن المستخدم بدّه force، نخليه pending مؤقتاً
            if (!empty($report->status) && $report->status === 'sent' && $force) {
                DB::table('weekly_video_reports_tables')
                    ->where('id', $report->id)
                    ->update(['status' => 'pending', 'updated_at' => now()]);
            }

            $response = $this->whatsapp($phone, $message);
            $ok = method_exists($response, 'successful') ? $response->successful() : true;
            if (!$ok) {
                $status = method_exists($response, 'status') ? $response->status() : null;
                $body = method_exists($response, 'body') ? $response->body() : null;
                $this->error('WhatsApp send failed (admin): phone=' . $phone . ' status=' . ($status ?? 'n/a'));
                if (!empty($body)) {
                    $this->error('WhatsApp response body: ' . $body);
                }

                throw new \RuntimeException('WhatsApp send failed for admin phone: ' . $phone);
            }

            DB::table('weekly_video_reports_tables')
                ->where('id', $report->id)
                ->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'message' => $message,
                    'updated_at' => now(),
                ]);

            DB::commit();
            $this->info('Weekly report sent successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            DB::rollBack();

            DB::table('weekly_video_reports_tables')
                ->where('id', $report->id)
                ->update([
                    'status' => 'failed',
                    'message' => 'Failed to send WhatsApp report: ' . $e->getMessage(),
                    'updated_at' => now(),
                ]);

            $this->error('Failed to send weekly report: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * @return array<int,string>
     */
    private function normalizePhones($phonesRaw): array
    {
        if (empty($phonesRaw)) {
            return [];
        }

        $phonesRaw = (string) $phonesRaw;

        // يسمح بالقائمة مفصولة بفواصل/نقطتين
        $parts = preg_split('/[,\n;]+/', $phonesRaw) ?: [];
        $parts = array_map(static fn ($p) => trim((string) $p), $parts);

        return array_values(array_filter($parts));
    }
}


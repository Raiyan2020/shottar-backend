<?php

namespace App\Jobs;

use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * إرسال إشعارات FCM في الخلفية.
 *
 * الإرسال كان بيحصل جوه ريكوست لوحة التحكم، وبطلب HTTP لكل جهاز على حدة، فصفحة
 * "إرسال إشعار" كانت بتعلّق دقايق مع عدد مستخدمين كبير (ومعرّضة لـ timeout من
 * الويب سيرفر في النص، فجزء من المستخدمين ميوصلهمش).
 *
 * التوكنات بتتقسّم دفعات وكل دفعة جوب مستقل، فلو دفعة فشلت الباقي بيكمّل.
 */
class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** عدد التوكنات في الجوب الواحد */
    public const CHUNK_SIZE = 500;

    public int $tries = 3;
    public int $timeout = 600;
    public int $backoff = 30;

    /**
     * @param  array<int, string>  $tokens
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $tokens,
        public string $title,
        public string $body,
        public array $data = [],
        public ?string $audience = null,
    ) {}

    public function handle(FirebaseNotificationService $service): void
    {
        $summary = $service->sendNotification($this->tokens, $this->title, $this->body, $this->data);

        Log::info('تم إرسال دفعة إشعارات', [
            'audience' => $this->audience,
            'title' => $this->title,
        ] + $summary);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('فشل جوب إرسال الإشعارات', [
            'audience' => $this->audience,
            'title' => $this->title,
            'tokens' => count($this->tokens),
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * بيقسّم التوكنات على جوبات ويرجّع عدد الجوبات اللي اتجدولت.
     *
     * @param  array<int, string>  $tokens
     * @param  array<string, mixed>  $data
     */
    public static function dispatchInChunks(
        array $tokens,
        string $title,
        string $body,
        array $data = [],
        ?string $audience = null
    ): int {
        $tokens = array_values(array_unique(array_filter($tokens)));

        if ($tokens === []) {
            return 0;
        }

        $chunks = array_chunk($tokens, self::CHUNK_SIZE);

        // بيروح على queue الافتراضية عشان الـ workers الموجودة على السيرفر
        // تشوفه من غير أي إعداد إضافي. لو عملت worker مخصّص للإشعارات، ظبّط
        // FCM_QUEUE في .env وشغّل الـ worker بـ --queue=<الاسم>.
        $queue = (string) config('services.fcm.queue', 'default');

        foreach ($chunks as $chunk) {
            self::dispatch($chunk, $title, $body, $data, $audience)->onQueue($queue);
        }

        return count($chunks);
    }
}

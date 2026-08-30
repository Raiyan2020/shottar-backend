<?php

namespace App\Jobs;

use App\Models\CourseMaterial;
use App\Services\VimeoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * بيجيب مدة الفيديو من Vimeo ويحفظها على الدرس.
 *
 * ليه جوب مؤجّل؟ لأن Vimeo بيرمّز الفيديو بعد الرفع، وطول فترة الترميز الـ API
 * بيرجّع duration = 0. الكود القديم كان بيسأل **فورًا بعد الرفع** وبيخزّن الصفر
 * ده كأنه نهائي — وده سبب إن كل المواد بتعرض "0 ساعة 0 دقيقة".
 */
class SyncVimeoDurationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** الترميز ممكن ياخد دقايق، فبنعيد المحاولة بتباعد متزايد */
    public int $tries = 6;
    public int $timeout = 60;

    public function __construct(public int $materialId) {}

    /** ثواني الانتظار بين المحاولات: 1د، 3د، 10د، 30د، ساعة */
    public function backoff(): array
    {
        return [60, 180, 600, 1800, 3600];
    }

    public function handle(VimeoService $vimeo): void
    {
        $material = CourseMaterial::find($this->materialId);

        if (! $material || $material->type !== 'lesson') {
            return;
        }

        $duration = $vimeo->fetchDuration($material->video);

        if ($duration === null) {
            // لسه بيترمّز أو الرابط مش Vimeo — نعيد المحاولة لاحقًا
            $this->release($this->backoff()[$this->attempts() - 1] ?? 3600);

            return;
        }

        $material->update([
            'duration' => $duration,
            'duration_text' => gmdate('H:i:s', $duration),
            'upload_status' => 'done',
        ]);

        Log::info('تم تحديث مدة الدرس من Vimeo', [
            'material_id' => $material->id,
            'duration' => $duration,
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Models\CourseMaterial;
use App\Services\VimeoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadVideoToVimeoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $material;
    protected $filePath;

    public function __construct(CourseMaterial $material, string $filePath)
    {
        $this->material = $material;
        $this->filePath = $filePath;
    }

    public function handle(VimeoService $vimeoService)
    {
        try {
            $this->material->update(['upload_status' => 'processing']);
            \Log::info('Vimeo upload job started', [
                'material_id' => $this->material->id,
                'file_exists' => file_exists($this->filePath),
            ]);
            $videoFile = new UploadedFile(
                $this->filePath,               // المسار الفعلي للملف
                basename($this->filePath),     // الاسم الأصلي
                null,                           // MIME type (يمكن تركه null)
                null,                           // حجم الملف (optional)
                true                            // $test = true لتجاهل التحقق من الرفع
            );
            $vimeoResult = $vimeoService->uploadVideo($videoFile);

            if ($vimeoResult && !empty($vimeoResult['link'])) {
                $this->material->update([
                    'video' => $vimeoResult['link'],
                    'duration' => (int)($vimeoResult['duration'] ?? 0),
                    'duration_text' => $vimeoResult['duration_text'] ?? null,
                    'upload_status' => 'done',
                ]);
            } else {
                $this->material->update(['upload_status' => 'failed']);
                \Log::error('Vimeo upload returned empty link', ['material_id' => $this->material->id]);
            }
        } catch (\Throwable $e) {
            \Log::error('Vimeo upload job failed: '.$e->getMessage(), ['material_id' => $this->material->id]);
            $this->material->update(['upload_status' => 'failed']);
        }
    }
}

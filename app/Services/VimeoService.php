<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Vimeo\Vimeo;
use FFMpeg\FFProbe;
use Vimeo\Exceptions\VimeoUploadException;

class VimeoService
{
    protected Vimeo $client;

    public function __construct()
    {
        $this->client = new Vimeo(
            config('services.vimeo.client_id'),
            config('services.vimeo.client_secret'),
            config('services.vimeo.access_token')
        );
    }

    public function uploadVideo(UploadedFile $file): ?array
    {
        try {
            $filePath = $file->getRealPath();

            // حساب المدة باستخدام FFProbe
//            $ffprobe = FFProbe::create();
//            $durationInSeconds = (int) $ffprobe
//                ->format($filePath)
//                ->get('duration');

            // رفع الفيديو على Vimeo
            $uri = $this->client->upload(
                $filePath,
                [
                    'name' => $file->getClientOriginalName(),
                    'privacy' => [
                        'view' => 'unlisted'
                    ]
                ],
                null,          // ✅ لا تمرّر string
                5 * 1024 * 1024 // ✅ حجم chunk 5MB لتقليل استهلاك الذاكرة
            );

            // جلب البيانات
            $videoData = $this->client->request($uri . '?fields=link');

            return [
                'link'     => $videoData['body']['link'] ?? null,
                'duration' => $durationInSeconds ?? 0,
            ];

        } catch (VimeoUploadException $e) {
            // يطبع رسالة الـ Vimeo مباشرة
            Log::error('Vimeo Upload Error: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'body' => method_exists($e, 'getBody') ? $e->getBody() : null,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // نخليه يمر للـ Controller عشان يبين السبب
        } catch (\Exception $e) {
            Log::error('General Vimeo Error: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // برضه نرميه عشان تعرف السبب
        }
    }


    public function getClient(): Vimeo
    {
        return $this->client;
    }
}

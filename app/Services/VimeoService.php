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

    /**
     * بيستخرج رقم الفيديو من رابط Vimeo أو من uri زي /videos/123.
     */
    public static function extractVideoId(?string $videoUrlOrUri): ?string
    {
        $value = trim((string) $videoUrlOrUri);

        if ($value === '') {
            return null;
        }

        if (preg_match('~(?:vimeo\\.com/(?:video/)?|/videos?/|^)(\\d{6,})~', $value, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * مدة الفيديو بالثواني من Vimeo API.
     *
     * بترجع null لو الفيديو لسه بيتعالج أو حصل خطأ — مهم نفرّق بين ده وبين
     * صفر حقيقي، عشان منخزّنش صفر ونعتبره نهائي.
     */
    public function fetchDuration(?string $videoUrlOrUri): ?int
    {
        $videoId = self::extractVideoId($videoUrlOrUri);

        if (! $videoId) {
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken(config('services.vimeo.access_token'))
                ->withHeaders(['Accept' => 'application/vnd.vimeo.*+json;version=3.4'])
                ->timeout(20)
                ->get("https://api.vimeo.com/videos/{$videoId}", ['fields' => 'duration,status,transcode']);

            if (! $response->successful()) {
                Log::warning('Vimeo duration fetch failed', [
                    'video_id' => $videoId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $duration = (int) ($response->json('duration') ?? 0);

            // لسه بيترمز → المدة مش جاهزة
            if ($duration <= 0) {
                return null;
            }

            return $duration;
        } catch (\Throwable $e) {
            Log::warning('Vimeo duration fetch exception', [
                'video_id' => $videoId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

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

            // جلب البيانات — لازم duration كمان.
            // كان بيرجّع $durationInSeconds وهو متغير **مش معرّف** (كود الـ
            // ffprobe فوق متعمّله comment)، فالمدة كانت بتتخزن 0 دايمًا.
            $videoData = $this->client->request($uri . '?fields=link,duration');
            $duration = (int) ($videoData['body']['duration'] ?? 0);

            return [
                'link'     => $videoData['body']['link'] ?? null,
                'uri'      => $uri,
                'duration' => $duration,
                'duration_text' => $duration > 0 ? gmdate('H:i:s', $duration) : null,
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

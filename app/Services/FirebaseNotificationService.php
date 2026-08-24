<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected $firebaseService;
    protected $firebaseServerKey;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
        $this->firebaseServerKey = env('FCM_SERVER_KEY');
    }

    /**
     * إرسال إشعار لمجموعة توكنات.
     *
     * كان بيبعت **طلب HTTP متزامن لكل جهاز على حدة** في لوب، يعني 1000 مستخدم
     * = 1000 رحلة متسلسلة لـ FCM جوه الريكوست (دقايق). دلوقتي بيبعت على دفعات
     * متوازية بـ Http::pool، وبيرجّع ملخّص، وبينضّف التوكنات الميتة.
     *
     * @return array{sent:int,failed:int,invalid:int,total:int}
     */
    public function sendNotification(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        $deviceTokens = array_values(array_unique(array_filter($deviceTokens)));

        $summary = ['sent' => 0, 'failed' => 0, 'invalid' => 0, 'total' => count($deviceTokens)];

        if ($deviceTokens === []) {
            return $summary;
        }

        $url = 'https://fcm.googleapis.com/v1/projects/shottar-d93f6/messages:send';
        $accessToken = $this->firebaseService->getAccessToken();

        if (! $accessToken) {
            Log::error('FCM: تعذر الحصول على access token — مفيش إشعارات اتبعتت');
            $summary['failed'] = $summary['total'];

            return $summary;
        }

        // FCM data payload values must be strings.
        // Do NOT repeat title/body here — that causes Flutter to show the same alert twice
        // (system tray from `notification` + local UI from `data`).
        $dataPayload = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            $dataPayload[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        if (! isset($dataPayload['type'])) {
            $dataPayload['type'] = 'general';
        }

        $deadTokens = [];

        // عدد الطلبات المتوازية في الدفعة الواحدة
        $concurrency = max(1, (int) config('services.fcm.concurrency', 25));

        foreach (array_chunk($deviceTokens, $concurrency) as $chunk) {
            $responses = Http::pool(function ($pool) use ($chunk, $url, $accessToken, $title, $body, $dataPayload) {
                foreach ($chunk as $token) {
                    $pool->as((string) $token)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type' => 'application/json',
                        ])
                        ->timeout(15)
                        ->post($url, [
                            'message' => [
                                'token' => $token,
                                'notification' => [
                                    'title' => $title,
                                    'body' => $body,
                                ],
                                'android' => [
                                    'priority' => 'high',
                                    'notification' => [
                                        'sound' => 'default',
                                        'channel_id' => 'default',
                                    ],
                                ],
                                'apns' => [
                                    'payload' => [
                                        'aps' => [
                                            'sound' => 'default',
                                        ],
                                    ],
                                ],
                                'data' => $dataPayload,
                            ],
                        ]);
                }
            });

            foreach ($responses as $token => $response) {
                // الاستثناءات (timeout/DNS) بترجع كـ exception مش response
                if ($response instanceof \Throwable) {
                    $summary['failed']++;
                    Log::warning('FCM send exception', [
                        'token' => substr((string) $token, 0, 12) . '...',
                        'error' => $response->getMessage(),
                    ]);

                    continue;
                }

                if ($response->successful()) {
                    $summary['sent']++;

                    continue;
                }

                $summary['failed']++;
                $errorStatus = (string) data_get($response->json(), 'error.status');

                // التوكن ده مات (المستخدم شال التطبيق / التوكن اتغير) — نشيله
                // عشان ميستهلكش طلب في كل إرسال جاي.
                if (in_array($errorStatus, ['UNREGISTERED', 'NOT_FOUND'], true)) {
                    $deadTokens[] = (string) $token;
                    $summary['invalid']++;

                    continue;
                }

                Log::warning('FCM send failed', [
                    'token' => substr((string) $token, 0, 12) . '...',
                    'status' => $response->status(),
                    'error' => $errorStatus,
                ]);
            }
        }

        if ($deadTokens !== []) {
            \App\Models\User::whereIn('device_token', $deadTokens)->update(['device_token' => null]);
            Log::info('FCM: تم تنظيف توكنات ميتة', ['count' => count($deadTokens)]);
        }

        return $summary;
    }

    public function sendFCMTopic($data, $target = 'general')
    {
        $url = 'https://fcm.googleapis.com/v1/projects/shottar-d93f6/messages:send';
        $accessToken = $this->firebaseService->getAccessToken();

        $dataPayload = [
            'type' => (string) ($data['type'] ?? 'general'),
        ];

        if (! empty($data['url'])) {
            $dataPayload['url'] = (string) $data['url'];
        }

        $payload = [
            'message' => [
                'topic' => $target,
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['body'],
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
                'data' => $dataPayload,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        return $response->json();
    }
}

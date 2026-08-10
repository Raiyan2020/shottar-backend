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

    public function sendNotification(array $deviceTokens, string $title, string $body, array $data = [])
    {
        $deviceTokens = array_values(array_unique(array_filter($deviceTokens)));

        if ($deviceTokens === []) {
            return false;
        }

        $url = 'https://fcm.googleapis.com/v1/projects/shottar-d93f6/messages:send';
        $accessToken = $this->firebaseService->getAccessToken();

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

        foreach ($deviceTokens as $token) {
            $payload = [
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
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->failed()) {
                Log::warning('FCM send failed', [
                    'token' => substr((string) $token, 0, 12) . '...',
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
            }
        }

        return true;
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

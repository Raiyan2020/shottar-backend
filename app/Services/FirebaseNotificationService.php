<?php

namespace App\Services;

use App\Services\FirebaseService;
use Illuminate\Support\Facades\Http;

class FirebaseNotificationService
{
    protected $firebaseService , $firebaseServerKey;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
        $this->firebaseServerKey = env('FCM_SERVER_KEY');
    }


    public function sendNotification(array $deviceTokens, string $title, string $body, array $data = [])
    {
//        $url = 'https://fcm.googleapis.com/fcm/send';
        $url = 'https://fcm.googleapis.com/v1/projects/shottar-d93f6/messages:send';
        $accessToken = $this->firebaseService->getAccessToken();

        foreach ($deviceTokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'android' => [
                        'notification' => [
                            'sound' => 'default', // إضافة الصوت لـ Android
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default', // إضافة الصوت لـ iOS
                            ],
                        ],
                    ],
                    'data' =>  [
                        'title' => $title,
                        'body' => $body,
                    ],
                ],
            ];
            // إرسال الإشعار
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);
            // جمع الردود
//            $responses[] = $response->json();
        }

        return true;
    }
    //sendFCMTopic
    public function sendFCMTopic($data, $target = 'general')
    {
        $url = 'https://fcm.googleapis.com/v1/projects/shottar-d93f6/messages:send';
        $accessToken = $this->firebaseService->getAccessToken();
        $payload = [
            'message' => [
                'topic' => $target,
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'title_en' => $data['title_en'] ?? null,
                    'body_en' => $data['body_en'] ?? null,
                    'url' => $data['url'] ?? null,

                ],
                'android' => [
                    'notification' => [
                        'sound' => 'default', // إضافة الصوت لـ Android
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default', // إضافة الصوت لـ iOS
                        ],
                    ],
                ],
                'data' =>  [
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'title_en' => $data['title_en'] ?? null,
                    'body_en' => $data['body_en'] ?? null,
                    'url' => $data['url'] ?? null,
                ],
            ],
        ];
        // إرسال الإشعار
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);
        // جمع الردود
        return $response->json();

    }
}

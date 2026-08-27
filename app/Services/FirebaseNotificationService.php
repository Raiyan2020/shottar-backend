<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    public function __construct(private readonly Messaging $messaging) {}

    /**
     * Send a visible Firebase notification to a list of device tokens.
     *
     * This follows the same SDK-based payload used by the Nafas application
     * and doesn't force an Android channel ID that may not exist in Flutter.
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

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($this->stringifyData(array_merge($data, [
                'type' => $data['type'] ?? 'general',
                'title' => $title,
                'body' => $body,
            ])))
            ->withDefaultSounds();

        $deadTokens = [];

        foreach (array_chunk($deviceTokens, 500) as $tokens) {
            try {
                $report = $this->messaging->sendMulticast($message, $tokens);
                $sent = $report->successes()->count();
                $failed = $report->failures()->count();
                $invalidTokens = array_values(array_unique(array_merge(
                    $report->invalidTokens(),
                    $report->unknownTokens(),
                )));

                $summary['sent'] += $sent;
                $summary['failed'] += $failed;
                $summary['invalid'] += count($invalidTokens);
                $deadTokens = array_merge($deadTokens, $invalidTokens);

                if ($failed > 0) {
                    Log::warning('Firebase multicast partially failed', [
                        'sent' => $sent,
                        'failed' => $failed,
                        'invalid' => count($invalidTokens),
                    ]);
                }
            } catch (\Throwable $exception) {
                $summary['failed'] += count($tokens);
                Log::error('Firebase multicast failed', [
                    'tokens_count' => count($tokens),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $deadTokens = array_values(array_unique($deadTokens));

        if ($deadTokens !== []) {
            User::whereIn('device_token', $deadTokens)->update(['device_token' => null]);
            Log::info('FCM: removed dead device tokens', ['count' => count($deadTokens)]);
        }

        return $summary;
    }

    public function sendFCMTopic(array $data, string $target = 'general'): array
    {
        try {
            $message = CloudMessage::withTarget('topic', ltrim($target, '/'))
                ->withNotification(Notification::create(
                    (string) ($data['title'] ?? ''),
                    (string) ($data['body'] ?? ''),
                ))
                ->withData($this->stringifyData(array_merge(['type' => 'general'], $data)))
                ->withDefaultSounds();

            $messageId = $this->messaging->send($message);

            return ['success' => true, 'name' => $messageId];
        } catch (\Throwable $exception) {
            Log::error('Firebase topic send failed', [
                'topic' => $target,
                'message' => $exception->getMessage(),
            ]);

            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    private function stringifyData(array $data): array
    {
        return collect($data)
            ->reject(fn ($value) => $value === null)
            ->map(fn ($value) => is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE))
            ->all();
    }
}

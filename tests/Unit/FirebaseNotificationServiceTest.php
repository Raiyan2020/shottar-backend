<?php

namespace Tests\Unit;

use App\Services\FirebaseNotificationService;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class FirebaseNotificationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_builds_the_same_visible_notification_and_data_payload_as_nafas(): void
    {
        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('sendMulticast')
            ->once()
            ->withArgs(function (CloudMessage $message, array $tokens): bool {
                $payload = $message->jsonSerialize();

                self::assertSame(['token-1'], $tokens);
                self::assertSame('Test title', $payload['notification']['title']);
                self::assertSame('Test body', $payload['notification']['body']);
                self::assertSame('Test title', $payload['data']['title']);
                self::assertSame('Test body', $payload['data']['body']);
                self::assertSame('admin_broadcast', $payload['data']['type']);
                self::assertArrayNotHasKey('channel_id', $payload['android']['notification'] ?? []);

                return true;
            })
            ->andThrow(new RuntimeException('Stop before a real Firebase request'));

        $result = (new FirebaseNotificationService($messaging))->sendNotification(
            ['token-1'],
            'Test title',
            'Test body',
            ['type' => 'admin_broadcast'],
        );

        self::assertSame([
            'sent' => 0,
            'failed' => 1,
            'invalid' => 0,
            'total' => 1,
        ], $result);
    }
}

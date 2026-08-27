<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\NotificationController;
use App\Services\FirebaseNotificationService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class AdminNotificationControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dashboard_uses_the_direct_firebase_service_and_reports_success(): void
    {
        $service = Mockery::mock(FirebaseNotificationService::class);
        $service->shouldReceive('sendNotification')
            ->once()
            ->with(['device-token'], 'Title', 'Body', ['type' => 'user'])
            ->andReturn(['sent' => 1, 'failed' => 0, 'invalid' => 0, 'total' => 1]);

        self::assertTrue($this->push(
            new NotificationController($service),
            ['device-token'],
        ));
    }

    public function test_dashboard_does_not_claim_success_when_no_device_token_exists(): void
    {
        $service = Mockery::mock(FirebaseNotificationService::class);
        $service->shouldNotReceive('sendNotification');

        self::assertFalse($this->push(new NotificationController($service), []));
    }

    private function push(NotificationController $controller, array $tokens): bool
    {
        $method = new ReflectionMethod($controller, 'pushOrWarn');

        return $method->invoke($controller, $tokens, [
            'title' => 'Title',
            'body' => 'Body',
        ], 'user');
    }
}

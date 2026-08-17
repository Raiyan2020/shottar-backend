<?php

namespace Tests\Unit;

use App\Models\Coupon;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CouponExpiryTest extends TestCase
{
    public function test_coupon_expiring_today_is_still_valid(): void
    {
        Carbon::setTestNow('2026-08-17 15:30:00');

        $coupon = new Coupon([
            'expires_at' => '2026-08-17 00:00:00',
        ]);

        $this->assertFalse($coupon->isExpired());
    }

    public function test_coupon_expired_yesterday_is_invalid(): void
    {
        Carbon::setTestNow('2026-08-17 15:30:00');

        $coupon = new Coupon([
            'expires_at' => '2026-08-16 23:59:59',
        ]);

        $this->assertTrue($coupon->isExpired());
    }

    public function test_expires_at_is_stored_at_end_of_day(): void
    {
        $coupon = new Coupon();
        $coupon->expires_at = '2026-08-17';

        $this->assertSame('2026-08-17 23:59:59', $coupon->expires_at->format('Y-m-d H:i:s'));
    }
}

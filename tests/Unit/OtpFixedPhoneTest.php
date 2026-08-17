<?php

namespace Tests\Unit;

use Tests\TestCase;

class OtpFixedPhoneTest extends TestCase
{
    public function test_fixed_otp_for_egyptian_test_number(): void
    {
        config(['services.otp.fixed_phones' => '201091626965:1234']);

        $this->assertSame('201091626965', normalize_phone_digits('+201091626965'));
        $this->assertSame(1234, otp_fixed_code_for_phone('+201091626965'));
        $this->assertSame(1234, generate_activation_code('+201091626965'));
        $this->assertTrue(uses_fixed_otp('+201091626965'));
    }

    public function test_other_numbers_still_get_random_otp_when_not_configured(): void
    {
        config(['services.otp.fixed_phones' => '201091626965:1234']);

        $this->assertNull(otp_fixed_code_for_phone('+96555558718'));
        $this->assertFalse(uses_fixed_otp('+96555558718'));
    }
}

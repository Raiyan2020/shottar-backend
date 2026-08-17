<?php

namespace Tests\Unit;

use App\Models\PaymentMethod;
use App\Models\Order;
use App\Models\User;
use App\Services\PaymentService;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_cash_is_offline_payment_method(): void
    {
        $this->assertTrue(PaymentMethod::isOffline(PaymentMethod::SLUG_CASH));
    }

    public function test_knet_uses_myfatoorah(): void
    {
        $this->assertTrue(PaymentMethod::usesMyFatoorah('knet'));
        $this->assertTrue(PaymentMethod::usesMyFatoorah('visa'));
    }

    public function test_cash_does_not_use_myfatoorah(): void
    {
        $this->assertFalse(PaymentMethod::usesMyFatoorah(PaymentMethod::SLUG_CASH));
    }

    public function test_cash_is_not_mapped_to_myfatoorah_method_id(): void
    {
        $this->assertArrayNotHasKey(PaymentMethod::SLUG_CASH, PaymentMethod::ALL_METHODS);
    }
}

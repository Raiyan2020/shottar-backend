<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use Tests\TestCase;

class DashboardLanguageSwitchTest extends TestCase
{
    public function test_dashboard_language_can_be_switched_to_arabic(): void
    {
        $response = $this
            ->withoutMiddleware(Authenticate::class)
            ->from('/teacher/dashboard')
            ->post(route('dashboard.language.switch', 'ar'));

        $response->assertRedirect('/teacher/dashboard');
        $response->assertSessionHas('dashboard_locale', 'ar');
        $response->assertCookie('dashboard_locale', 'ar');

        $this->get('/test')->assertOk();
        $this->assertSame('ar', app()->getLocale());
    }

    public function test_unsupported_dashboard_language_is_not_available(): void
    {
        $this
            ->withoutMiddleware(Authenticate::class)
            ->post('/dashboard/language/fr')
            ->assertNotFound();
    }
}

<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * وسيلة الدفع الوحيدة اللي بتترجع وقت مراجعة آبل.
     *
     * قابلة للتغيير من الإعدادات عشان لو آبل رفضت أو التطبيق نزل بفلترة
     * مختلفة، تتظبط في ثانية من غير نشر كود — والمراجعة عادة بتبقى ضيقة الوقت.
     */
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key_id' => 'review_payment_slug'],
            [
                'title_en' => 'Payment slug shown during Apple review (cash / apple_iap)',
                'title_ar' => 'وسيلة الدفع أثناء مراجعة آبل (cash أو apple_iap)',
                'value' => 'cash',
                'set_group' => 'app',
                'is_object' => '1',
            ]
        );
    }

    public function down(): void
    {
        Setting::where('key_id', 'review_payment_slug')->delete();
    }
};

<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * §9 — نسخة iOS اللي تحت مراجعة آبل حاليًا.
     *
     * فاضية = مفيش نسخة تحت المراجعة (الوضع الطبيعي). لما تتحط نسخة زي 1.1.5،
     * الطلبات الجاية من iOS بنفس رقم النسخة بالظبط بتاخد is_reviewer = true،
     * فمراجع آبل ميشوفش غير الشراء داخل التطبيق.
     */
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key_id' => 'ios_review_version'],
            [
                'title_en' => 'iOS version under App Review (leave empty when none)',
                'title_ar' => 'إصدار iOS تحت مراجعة آبل (اتركه فارغًا لو مفيش)',
                'value' => '',
                'set_group' => 'app',
                'is_object' => '1',
            ]
        );
    }

    public function down(): void
    {
        Setting::where('key_id', 'ios_review_version')->delete();
    }
};

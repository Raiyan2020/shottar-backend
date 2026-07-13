<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ForcedUpdateSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Forced update for Android
        Setting::updateOrCreate(
            ['key_id' => 'forced_update_android'],
            [
                'title_en' => 'Forced Update (Android)',
                'title_ar' => 'تحديث إجباري (أندرويد)',
                'value' => '1', // يمكنك وضع رقم النسخة المطلوبة مثلاً: '2.5.0'
                'set_group' => 'app',
                'is_object' => '1',
            ]
        );

// Forced update for iOS
        Setting::updateOrCreate(
            ['key_id' => 'forced_update_ios'],
            [
                'title_en' => 'Forced Update (iOS)',
                'title_ar' => 'تحديث إجباري (iOS)',
                'value' => '1',
                'set_group' => 'app',
                'is_object' => '1',
            ]
        );

        Setting::updateOrCreate(
            ['key_id' => 'android_version'],
            [
                'title_en' => 'Android Version',
                'title_ar' => 'إصدار أندرويد',
                'value'    => '1.0.0',
                'set_group'=> 'app',
                'is_object'=> '1',
            ]
        );

        Setting::updateOrCreate(
            ['key_id' => 'ios_version'],
            [
                'title_en' => 'iOS Version',
                'title_ar' => 'إصدار iOS',
                'value'    => '1.0.0',
                'set_group'=> 'app',
                'is_object'=> '1',
            ]
        );

        Setting::updateOrCreate(
            ['key_id' => 'force_close'],
            [
                'title_en' => 'Force Close',
                'title_ar' => 'إغلاق إجباري',
                'value'    => '0',
                'set_group'=> 'app',
                'is_object'=> '1',
            ]
        );





    }
}

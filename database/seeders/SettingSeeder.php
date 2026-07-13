<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::truncate();



        Setting::create([
            'key_id' => 'terms_ar',
            'title_en'=>'Terms and Conditions Arabic',
            'title_ar'=>'الشروط والاحكام عربي',
            'value' => 'الشروط والاحكام باللغة العربية',
            'set_group' => 'app',
            'is_object' => '1',
        ]);

        Setting::create([
            'key_id' => 'terms_en',
            'title_en'=>'Terms and Conditions English',
            'title_ar'=>'الشروط والاحكام انجليزي',
            'value' => 'Terms and conditions in English',
            'set_group' => 'app',
            'is_object' => '1',
        ]);

        Setting::create([
            'key_id' => 'privacy_policy_ar',
            'title_en'=>'Privacy Policy Arabic',
            'title_ar'=>'سياسة الخصوصية عربي',
            'value' => 'سياسة الخصوصية باللغة العربية',
            'set_group' => 'app',
            'is_object' => '1',
        ]);

        Setting::create([
            'key_id' => 'privacy_policy_en',
            'title_en'=>'Privacy Policy English',
            'title_ar'=>'سياسة الخصوصية انجليزي',
            'value' => 'Privacy policy in English',
            'set_group' => 'app',
            'is_object' => '1',
        ]);

        //instegram
        Setting::create([
            'key_id' => 'instagram',
            'title_en'=>'Instagram',
            'title_ar'=>'انستغرام',
            'value' => 'https://www.instagram.com/yourprofile',
            'set_group' => 'app',
            'is_object' => '1',
        ]);

        //twitter
        Setting::create([
            'key_id' => 'twitter',
            'title_en'=>'Twitter',
            'title_ar'=>'تويتر',
            'value' => 'https://www.twitter.com/yourprofile',
            'set_group' => 'app',
            'is_object' => '1',
        ]);

        //tiktok
        Setting::create([
            'key_id' => 'tiktok',
            'title_en'=>'TikTok',
            'title_ar'=>'تيك توك',
            'value' => 'https://www.tiktok.com/yourprofile',
            'set_group' => 'app',
            'is_object' => '1',
        ]);
        //phone
        Setting::create([
            'key_id' => 'phone',
            'title_en'=>'Phone',
            'title_ar'=>'الهاتف',
            'value' => '+1234567890',
            'set_group' => 'app',
            'is_object' => '1',
        ]);







    }
}

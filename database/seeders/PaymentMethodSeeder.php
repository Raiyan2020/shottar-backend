<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run()
    {
        $methods = [
            [
                'slug' => 'apple_iap',
                'name_ar' => 'شراء داخل التطبيق (آبل)',
                'name_en' => 'Apple In-App Purchase',
            ],
            [
                'slug' => 'apple-pay',
                'name_ar' => 'دفع آبل',
                'name_en' => 'Apple Pay',
            ],
            [
                'slug' => 'knet',
                'name_ar' => 'كي نت',
                'name_en' => 'Knet',
            ],
            [
                'slug' => 'visa',
                'name_ar' => 'فيزا',
                'name_en' => 'Visa',
            ],
            [
                'slug' => 'cash',
                'name_ar' => 'نقدي',
                'name_en' => 'CASH',
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['slug' => $method['slug']],
                [
                    'name_ar' => $method['name_ar'],
                    'name_en' => $method['name_en'],
                    'image' => null,
                    'status' => true,
                ]
            );
        }
    }
}

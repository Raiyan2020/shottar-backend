<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run()
    {
        PaymentMethod::firstOrCreate(
            ['slug' => 'apple_iap'],
            [
                'name_ar' => 'شراء داخل التطبيق (آبل)',
                'name_en' => 'Apple In-App Purchase',
                'image' => null,
                'status' => true,
            ]
        );

       //apple pay
        PaymentMethod::create([
            'name_ar' => 'دفع آبل',
            'name_en' => 'Apple Pay',
            'image' => null,
            'slug' => 'apple-pay',
            'status' => true,
        ]);
        //كي نت
        PaymentMethod::create([
            'name_ar' => 'كي نت',
            'name_en' => 'Knet',
            'image' => null,
            'slug' => 'knet',
            'status' => true,
        ]);
        //فيزا
        PaymentMethod::create([
            'name_ar' => 'فيزا',
            'name_en' => 'Visa',
            'image' => null,
            'slug' => 'visa',
            'status' => true,
        ]);


    }
}

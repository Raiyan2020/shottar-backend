<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grades = [
            ['name_ar' => 'الصف الأول', 'name_en' => 'Grade 1'],
            ['name_ar' => 'الصف الثاني', 'name_en' => 'Grade 2'],
            ['name_ar' => 'الصف الثالث', 'name_en' => 'Grade 3'],
            ['name_ar' => 'الصف الرابع', 'name_en' => 'Grade 4'],
            ['name_ar' => 'الصف الخامس', 'name_en' => 'Grade 5'],
            ['name_ar' => 'الصف السادس', 'name_en' => 'Grade 6'],
            ['name_ar' => 'الصف السابع', 'name_en' => 'Grade 7'],
            ['name_ar' => 'الصف الثامن', 'name_en' => 'Grade 8'],
            ['name_ar' => 'الصف التاسع', 'name_en' => 'Grade 9'],
            ['name_ar' => 'الصف العاشر', 'name_en' => 'Grade 10'],
            ['name_ar' => 'الصف الحادي عشر', 'name_en' => 'Grade 11'],
            ['name_ar' => 'الصف الثاني عشر', 'name_en' => 'Grade 12'],

        ];

        foreach ($grades as $grade) {
            Grade::create($grade);
        }
    }
}

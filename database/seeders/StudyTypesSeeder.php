<?php

namespace Database\Seeders;

use App\Models\StudyType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudyTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $types = [
            ['name_ar' => 'علمي', 'name_en' => 'Scientific', 'status' => true],
            ['name_ar' => 'أدبي', 'name_en' => 'Literary', 'status' => true],
        ];
        foreach ($types as $type) {
            StudyType::create($type);
        }
    }
}

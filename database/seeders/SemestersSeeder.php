<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SemestersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $semesters = [
            ['name_ar' => 'الفصل الدراسي الأول', 'name_en' => 'First Semester'],
            ['name_ar' => 'الفصل الدراسي الثاني', 'name_en' => 'Second Semester'],
        ];

        foreach ($semesters as $semester) {
            Semester::create($semester);
        }
    }
}

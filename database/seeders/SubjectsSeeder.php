<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\Semester;
use App\Models\StudyType;
use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grade = Grade::first();
        $semester = Semester::first();
        $studyType = StudyType::first();
        if (!$grade || !$semester || !$studyType) {
            $this->command->warn('يجب أن تتأكد من وجود البيانات الأساسية (grade, semester, study type) قبل تشغيل Seeder المواد.');
            return;
        }
        $subjects = [
            ['name_ar' => 'الرياضيات', 'name_en' => 'Mathematics'],
            ['name_ar' => 'العلوم', 'name_en' => 'Science'],
            ['name_ar' => 'اللغة العربية', 'name_en' => 'Arabic Language'],
            ['name_ar' => 'اللغة الإنجليزية', 'name_en' => 'English Language'],
        ];
        foreach ($subjects as $subject) {
            Subject::create([
                'name_ar' => $subject['name_ar'],
                'name_en' => $subject['name_en'],
                'grade_id' => $grade->id,
                'study_type_id' => $studyType->id,
                'semester_id' => $semester->id,
                'price' => 25,
                'image' => null,
                'duration' => '60 minutes',
                'status' => true,
            ]);
        }
    }
}

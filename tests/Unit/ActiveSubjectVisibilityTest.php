<?php

namespace Tests\Unit;

use App\Models\Subject;
use App\Models\CourseMaterial;
use App\Models\LessonSection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActiveSubjectVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('ios_product_id')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('lesson_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_id');
            $table->string('name_ar');
            $table->string('name_en');
            $table->boolean('status')->default(true);
            $table->unsignedInteger('order_by')->default(0);
            $table->timestamps();
        });

        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('lesson_section_id')->nullable();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('type');
            $table->boolean('status')->default(true);
            $table->unsignedInteger('order_by')->default(0);
            $table->timestamps();
        });
    }

    public function test_active_scope_excludes_inactive_subjects_from_mobile_queries(): void
    {
        Subject::create([
            'name_ar' => 'جيولوجيا',
            'name_en' => 'Geology',
            'status' => false,
        ]);

        Subject::create([
            'name_ar' => 'علوم',
            'name_en' => 'Science',
            'status' => true,
        ]);

        $this->assertSame(['Science'], Subject::active()->pluck('name_en')->all());
        $this->assertNull(Subject::active()->where('name_en', 'Geology')->first());
    }

    public function test_mobile_materials_exclude_inactive_content_and_inactive_sections(): void
    {
        $subject = Subject::create([
            'name_ar' => 'جيولوجيا',
            'name_en' => 'Geology',
            'status' => true,
        ]);

        $activeSection = LessonSection::create([
            'subject_id' => $subject->id,
            'name_ar' => 'وحدة مفعلة',
            'name_en' => 'Active unit',
            'status' => true,
        ]);
        $inactiveSection = LessonSection::create([
            'subject_id' => $subject->id,
            'name_ar' => 'وحدة غير مفعلة',
            'name_en' => 'Inactive unit',
            'status' => false,
        ]);

        foreach ([
            ['Visible lesson', true, $activeSection->id],
            ['Inactive lesson', false, $activeSection->id],
            ['Lesson in inactive unit', true, $inactiveSection->id],
            ['Visible ungrouped note', true, null],
        ] as [$name, $status, $sectionId]) {
            CourseMaterial::create([
                'subject_id' => $subject->id,
                'lesson_section_id' => $sectionId,
                'name_ar' => $name,
                'name_en' => $name,
                'type' => $sectionId ? 'lesson' : 'note',
                'status' => $status,
            ]);
        }

        $this->assertSame(
            ['Visible lesson', 'Visible ungrouped note'],
            $subject->activeCourseMaterials()->pluck('name_en')->all()
        );
    }
}

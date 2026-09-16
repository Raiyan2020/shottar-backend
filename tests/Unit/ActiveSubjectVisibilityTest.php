<?php

namespace Tests\Unit;

use App\Models\Subject;
use App\Models\CourseMaterial;
use App\Models\LessonSection;
use App\Http\Resources\SubjectDetailResource;
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
            $table->unsignedBigInteger('core_subject_id')->nullable();
            $table->unsignedBigInteger('grade_id')->nullable();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('ios_product_id')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('duration')->nullable();
            $table->string('image')->nullable();
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
            $table->unsignedInteger('duration')->nullable();
            $table->string('video')->nullable();
            $table->string('file')->nullable();
            $table->boolean('is_free')->default(false);
            $table->boolean('status')->default(true);
            $table->unsignedInteger('order_by')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('subject_id');
            $table->timestamps();
        });

        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('lesson_section_id')->nullable();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('file')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_free')->default(false);
            $table->unsignedInteger('order_by')->nullable();
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

    public function test_subject_details_payload_does_not_contain_inactive_materials(): void
    {
        $subject = Subject::create([
            'name_ar' => 'جيولوجيا',
            'name_en' => 'Geology',
            'status' => true,
        ]);

        $section = LessonSection::create([
            'subject_id' => $subject->id,
            'name_ar' => 'الاختبارات القصيرة',
            'name_en' => 'Short tests',
            'status' => false,
        ]);

        CourseMaterial::create([
            'subject_id' => $subject->id,
            'lesson_section_id' => $section->id,
            'name_ar' => 'علم الجيولوجيا',
            'name_en' => 'Geology science',
            'type' => 'lesson',
            'status' => false,
        ]);

        $subject->load(['activeCourseMaterials.section', 'exams']);
        $payload = (new SubjectDetailResource($subject))->response()->getData(true);

        $this->assertSame([], $payload['data']['sections']);
        $this->assertSame(0, $payload['data']['total_lessons']);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class CourseMaterialSortingViewTest extends TestCase
{
    public function test_materials_page_without_a_unit_does_not_build_a_reorder_url(): void
    {
        $this->view('dashboard.admin.course_materials._sorting', [
            'sectionId' => null,
            'type' => 'lesson',
            'reorderRouteName' => 'teacher.materials.reorder',
        ])->assertDontSee('Sortable.create', false);
    }

    public function test_teacher_materials_in_a_unit_build_the_correct_reorder_url(): void
    {
        $url = route('teacher.materials.reorder', [
            'type' => 'lesson',
            'section' => 105,
        ]);

        $this->view('dashboard.admin.course_materials._sorting', [
            'sectionId' => 105,
            'type' => 'lesson',
            'reorderRouteName' => 'teacher.materials.reorder',
        ])->assertSee(json_encode($url), false);
    }

    public function test_admin_materials_in_a_unit_build_the_correct_reorder_url(): void
    {
        $url = route('admin.materials.reorder', [
            'type' => 'note',
            'section' => 105,
        ]);

        $this->view('dashboard.admin.course_materials._sorting', [
            'sectionId' => 105,
            'type' => 'note',
            'reorderRouteName' => 'admin.materials.reorder',
        ])->assertSee(json_encode($url), false);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class CourseMaterialSortingViewTest extends TestCase
{
    public function test_materials_page_without_a_unit_loads_arrows_without_drag_url(): void
    {
        $this->view('dashboard.admin.course_materials._sorting', [
            'sectionId' => null,
            'subject' => (object) ['id' => 9],
            'type' => 'lesson',
            'reorderRouteName' => 'teacher.materials.reorder',
        ])->assertSee('js-move-up', false)
            ->assertSee('js-move-down', false)
            ->assertSee('const REORDER_URL = null', false);
    }

    public function test_teacher_materials_in_a_unit_build_the_correct_reorder_url(): void
    {
        $url = route('teacher.materials.reorder', [
            'type' => 'lesson',
            'section' => 105,
            'subject' => 9,
        ]);

        $this->view('dashboard.admin.course_materials._sorting', [
            'sectionId' => 105,
            'subject' => (object) ['id' => 9],
            'type' => 'lesson',
            'reorderRouteName' => 'teacher.materials.reorder',
        ])->assertSee(json_encode($url), false);
    }

    public function test_admin_materials_in_a_unit_build_the_correct_reorder_url(): void
    {
        $url = route('admin.materials.reorder', [
            'type' => 'note',
            'section' => 105,
            'subject' => 9,
        ]);

        $this->view('dashboard.admin.course_materials._sorting', [
            'sectionId' => 105,
            'subject' => (object) ['id' => 9],
            'type' => 'note',
            'reorderRouteName' => 'admin.materials.reorder',
        ])->assertSee(json_encode($url), false);
    }
}

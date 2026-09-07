<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardOrderingRoutesTest extends TestCase
{
    public function test_admin_exam_ordering_routes_are_registered(): void
    {
        $this->assertStringContainsString(
            '/admin/subjects/9/exams/reorder',
            route('admin.subjects.exams.reorder', ['subject' => 9])
        );

        $this->assertStringContainsString(
            '/admin/subjects/9/exams/41/move',
            route('admin.subjects.exams.move', ['subject' => 9, 'exam' => 41])
        );
    }

    public function test_admin_and_teacher_material_move_urls_keep_subject_scope(): void
    {
        foreach (['admin', 'teacher'] as $panel) {
            $url = route($panel.'.materials.move', [
                'type' => 'lesson',
                'section' => 105,
                'material' => 41,
                'subject' => 9,
            ]);

            $this->assertStringContainsString("/{$panel}/materials/lesson/105/41/move", $url);
            $this->assertStringContainsString('subject=9', $url);
        }
    }

    public function test_material_arrows_load_without_a_selected_section(): void
    {
        $view = $this->view('dashboard.admin.course_materials._sorting', [
            'sectionId' => null,
            'subject' => (object) ['id' => 9],
            'type' => 'lesson',
            'reorderRouteName' => 'teacher.materials.reorder',
        ]);

        $view->assertSee('js-move-up', false)
            ->assertSee('js-move-down', false)
            ->assertSee('const REORDER_URL = null', false);
    }

    public function test_material_drag_route_is_enabled_for_a_selected_section(): void
    {
        $view = $this->view('dashboard.admin.course_materials._sorting', [
            'sectionId' => 105,
            'subject' => (object) ['id' => 9],
            'type' => 'lesson',
            'reorderRouteName' => 'teacher.materials.reorder',
        ]);

        $expectedUrl = route('teacher.materials.reorder', [
            'type' => 'lesson',
            'section' => 105,
            'subject' => 9,
        ]);

        $view->assertSee(json_encode($expectedUrl), false);
    }
}

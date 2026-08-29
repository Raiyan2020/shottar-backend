<?php

namespace Tests\Feature;

use Tests\TestCase;

class CourseMaterialEditActionTest extends TestCase
{
    public function test_admin_lesson_actions_render_a_visible_edit_link(): void
    {
        $this->assertEditAction(
            'admin.subjects.materials.edit',
            route('admin.subjects.materials.edit', ['subject' => 9, 'material' => 45]),
        );
    }

    public function test_teacher_lesson_actions_render_a_visible_edit_link(): void
    {
        $this->assertEditAction(
            'teacher.subjects.materials.edit',
            route('teacher.subjects.materials.edit', ['subject' => 9, 'material' => 45]),
        );
    }

    private function assertEditAction(string $routeName, string $expectedUrl): void
    {
        $view = $this->view('components.datatable.actions', [
            'id' => 45,
            'subjectId' => 9,
            'nameUrl' => 'material',
            'routeEdit' => $routeName,
            'routeDelete' => null,
            'name' => 'Lesson',
            'showEditLabel' => true,
            'editTitle' => 'Edit',
        ]);

        $view->assertSee($expectedUrl, false)
            ->assertSee('bi-pencil-fill', false)
            ->assertSeeText('Edit');
    }
}

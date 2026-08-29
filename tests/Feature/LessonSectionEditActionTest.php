<?php

namespace Tests\Feature;

use Tests\TestCase;

class LessonSectionEditActionTest extends TestCase
{
    public function test_admin_unit_actions_render_a_visible_edit_link(): void
    {
        $this->assertEditAction(
            'admin.subjects.sections.edit',
            route('admin.subjects.sections.edit', ['subject' => 12, 'section' => 34]),
        );
    }

    public function test_teacher_unit_actions_render_a_visible_edit_link(): void
    {
        $this->assertEditAction(
            'teacher.subjects.sections.edit',
            route('teacher.subjects.sections.edit', ['subject' => 12, 'section' => 34]),
        );
    }

    private function assertEditAction(string $routeName, string $expectedUrl): void
    {
        $view = $this->view('components.datatable.actions', [
            'id' => 34,
            'subjectId' => 12,
            'nameUrl' => 'section',
            'routeEdit' => $routeName,
            'routeDelete' => null,
            'name' => 'Unit',
            'showEditLabel' => true,
            'editTitle' => 'Edit',
        ]);

        $view->assertSee($expectedUrl, false)
            ->assertSee('bi-pencil-fill', false)
            ->assertSeeText('Edit');
    }
}

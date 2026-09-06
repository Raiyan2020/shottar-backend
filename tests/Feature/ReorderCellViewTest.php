<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * خلية التحكم في الترتيب: المسكة هي الوحيدة اللي بتبدأ السحب، والأسهم
 * بتتقفل على حواف الترتيب العام.
 */
class ReorderCellViewTest extends TestCase
{
    protected function render(int $position, int $total): string
    {
        return (string) $this->view('dashboard.partials._reorder-cell', [
            'moveUrl' => '/admin/grades/7/move',
            'position' => $position,
            'total' => $total,
        ]);
    }

    public function test_the_cell_exposes_a_dedicated_drag_handle(): void
    {
        $html = $this->render(2, 5);

        // SortableJS متظبط على handle: '.js-drag-handle' — الكلاس ده هو
        // العقد بين الـ blade والـ script.
        $this->assertStringContainsString('js-drag-handle', $html);
    }

    public function test_move_url_and_global_position_are_exposed_to_javascript(): void
    {
        $html = $this->render(21, 45);

        $this->assertStringContainsString('data-move-url="/admin/grades/7/move"', $html);
        $this->assertStringContainsString('data-position="21"', $html);
        $this->assertStringContainsString('data-total="45"', $html);
    }

    public function test_first_row_has_move_up_disabled_only(): void
    {
        $html = $this->render(1, 5);

        $this->assertMatchesRegularExpression('/js-move-up[^>]*disabled/s', $html);
        $this->assertDoesNotMatchRegularExpression('/js-move-down[^>]*disabled/s', $html);
    }

    public function test_last_row_has_move_down_disabled_only(): void
    {
        $html = $this->render(5, 5);

        $this->assertMatchesRegularExpression('/js-move-down[^>]*disabled/s', $html);
        $this->assertDoesNotMatchRegularExpression('/js-move-up[^>]*disabled/s', $html);
    }

    public function test_middle_row_has_both_arrows_enabled(): void
    {
        $html = $this->render(3, 5);

        $this->assertStringNotContainsString('disabled', $html);
    }

    public function test_single_row_has_both_arrows_disabled(): void
    {
        $html = $this->render(1, 1);

        $this->assertMatchesRegularExpression('/js-move-up[^>]*disabled/s', $html);
        $this->assertMatchesRegularExpression('/js-move-down[^>]*disabled/s', $html);
    }

    // المراكز جاية من السيرفر، فالحواف بتتحسب على الترتيب العام مش على
    // ترتيب الصف في الصفحة الحالية.
    public function test_first_row_of_a_later_page_is_not_treated_as_a_boundary(): void
    {
        $html = $this->render(21, 45);

        $this->assertStringNotContainsString('disabled', $html);
    }
}

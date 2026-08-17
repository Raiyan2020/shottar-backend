<?php

namespace Tests\Unit;

use App\DataTables\TeachersDataTable;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeachersSubscribersCountTest extends TestCase
{
    public function test_subscribers_subquery_correlates_to_current_teacher(): void
    {
        $sql = (new TeachersDataTable)->query(new \App\Models\Admin)->toSql();

        $this->assertStringContainsString('`ts`.`teacher_id` = `admins`.`id`', $sql);
        $this->assertStringContainsString('COUNT(DISTINCT o.user_id)', $sql);
        $this->assertStringContainsString('`o`.`status` = ?', $sql);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الدينار الكويتي 3 خانات عشرية، والأعمدة كانت decimal(x,2) فأي مبلغ زي 5.125
     * كان بيتخزن 5.13. توسيع آمن (نفس القيم + خانة زيادة).
     */
    protected array $columns = [
        ['orders', 'total', 'DECIMAL(12,3) NOT NULL DEFAULT 0.000'],
        ['orders', 'discount', 'DECIMAL(12,3) NULL DEFAULT 0.000'],
        ['orders', 'discount_amount', 'DECIMAL(12,3) NULL DEFAULT 0.000'],
        ['order_items', 'price', 'DECIMAL(12,3) NOT NULL DEFAULT 0.000'],
        ['subjects', 'price', 'DECIMAL(12,3) NOT NULL DEFAULT 0.000'],
        ['grades', 'all_materials_price', 'DECIMAL(12,3) NULL DEFAULT 0.000'],
    ];

    public function up(): void
    {
        $this->apply($this->columns);
    }

    public function down(): void
    {
        $this->apply([
            ['orders', 'total', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00'],
            ['orders', 'discount', 'DECIMAL(10,2) NULL DEFAULT 0.00'],
            ['orders', 'discount_amount', 'DECIMAL(10,2) NULL DEFAULT 0.00'],
            ['order_items', 'price', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00'],
            ['subjects', 'price', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00'],
            ['grades', 'all_materials_price', 'DECIMAL(8,2) NULL DEFAULT 0.00'],
        ]);
    }

    protected function apply(array $columns): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($columns as [$table, $column, $definition]) {
            if (Schema::hasColumn($table, $column)) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}");
            }
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'subjects',
            'admins',
            'users',
            'payment_methods',
            'categories',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'image')) {
                continue;
            }

            DB::table($table)
                ->whereNotNull('image')
                ->get(['id', 'image'])
                ->each(function ($row) use ($table) {
                    $image = $row->image;

                    if (in_array(strtolower((string) $image), ['path_to_image', 'null', 'undefined', 'none', 'n/a'], true)) {
                        DB::table($table)->where('id', $row->id)->update(['image' => null]);

                        return;
                    }

                    if (is_string($image) && str_contains($image, '//')) {
                        DB::table($table)->where('id', $row->id)->update([
                            'image' => preg_replace('#/{2,}#', '/', $image),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }
};

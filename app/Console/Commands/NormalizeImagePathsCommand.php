<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeImagePathsCommand extends Command
{
    protected $signature = 'images:normalize-paths';

    protected $description = 'Normalize stored image paths (remove double slashes / invalid placeholders)';

    public function handle(): int
    {
        $tables = ['subjects', 'admins', 'users', 'payment_methods', 'categories'];
        $fixed = 0;

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'image')) {
                continue;
            }

            DB::table($table)
                ->whereNotNull('image')
                ->orderBy('id')
                ->get(['id', 'image'])
                ->each(function ($row) use ($table, &$fixed) {
                    $original = $row->image;
                    $normalized = normalize_public_path($original);

                    if (! is_public_image_path($normalized)) {
                        DB::table($table)->where('id', $row->id)->update(['image' => null]);
                        $fixed++;
                        $this->line("{$table}#{$row->id}: cleared invalid [{$original}]");

                        return;
                    }

                    if ($normalized !== $original) {
                        DB::table($table)->where('id', $row->id)->update(['image' => $normalized]);
                        $fixed++;
                        $this->line("{$table}#{$row->id}: [{$original}] -> [{$normalized}]");
                    }
                });
        }

        $this->info("Done. Updated {$fixed} row(s).");

        return self::SUCCESS;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subjects')
            ->whereNotNull('image')
            ->where('image', 'like', '%//%')
            ->get(['id', 'image'])
            ->each(function ($subject) {
                DB::table('subjects')
                    ->where('id', $subject->id)
                    ->update([
                        'image' => preg_replace('#/{2,}#', '/', $subject->image),
                    ]);
            });
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }
};

<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('teacher_subjects')->delete();
        DB::table('model_has_roles')->where('model_type', \App\Models\Admin::class)->delete();
        DB::table('model_has_permissions')->where('model_type', \App\Models\Admin::class)->delete();
        DB::table('admins')->delete();

        Schema::enableForeignKeyConstraints();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

//        Admin::create([
//            'name' => 'Super Admin',
//            'email' => 'admin@admin.com',
//            'password' => Hash::make('123456'),
//            'roles_name' => json_encode(['admin']),
//        ]);
        $super = Admin::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('123456')]
        );
        $super->assignRole('admin');

        $teacher = Admin::firstOrCreate(
            ['email' => 'teacher@example.com'],
            ['name' => 'Default Teacher', 'password' => 'password']
        );
        $teacher->assignRole('teacher');
    }
}

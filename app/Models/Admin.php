<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable


{
    use HasFactory ,HasRoles;

    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
//        'roles_name',
        'remember_token',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Laravel 10+: بيهاندل الهاش تلقائي
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',

    ];

    public function teachingSubjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects', 'teacher_id', 'subject_id')
            ->withPivot(['class_id', 'section_id'])
            ->withTimestamps();
    }
}

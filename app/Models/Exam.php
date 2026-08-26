<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'lesson_section_id',
        'name_ar',
        'name_en',
        'file',
        'status',
        'is_free',
        'order_by',
        'uploaded_by',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_free' => 'boolean',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /** الوحدة اللي المرفق تابع لها — ممكن تكون null يبقى المرفق على مستوى المادة كلها. */
    public function section()
    {
        return $this->belongsTo(LessonSection::class, 'lesson_section_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
    }
}

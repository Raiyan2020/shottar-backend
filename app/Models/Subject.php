<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'grade_id',
        'study_type_id',
        'semester_id',
        'price',
        'image',
        'duration',
        'status',
    ];

    // العلاقات (عشان نقدر نجيب المرحلة والفصل ونوع الدراسة)
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function studyType()
    {
        return $this->belongsTo(StudyType::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function lessonSections()
    {
        return $this->hasMany(LessonSection::class);
    }

    public function courseMaterials()
    {
        return $this->hasMany(CourseMaterial::class);
    }
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items', 'subject_id', 'order_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(Admin::class, 'teacher_subjects', 'subject_id', 'teacher_id')
            ->withPivot(['class_id','section_id'])
            ->withTimestamps();
    }

    public function progressPercentForUser(int $userId): float
    {
        // اجلب الدروس (ممكن تضيف ->where('status', 1) لو لازم)
        $lessons = $this->relationLoaded('courseMaterials')
            ? $this->courseMaterials->where('type', 'lesson')
            : $this->lessons()->get(['id']); // يكفينا IDs

        $totalLessons = $lessons->count();
        if ($totalLessons === 0) {
            return 0.0;
        }

        $lessonIds = $lessons->pluck('id');

        // عدد الدروس التي تمت مشاهدتها (أي وُجد لها سجل مشاهدة بمدة > 0)
        $viewedCount = \App\Models\MaterialView::query()
            ->where('user_id', $userId)
            ->whereIn('course_material_id', $lessonIds)
            ->where('duration_watched', '>', 0)
            ->distinct('course_material_id')
            ->count('course_material_id');

        return round(($viewedCount / $totalLessons) * 100, 2);
    }

    public function getDisplayLabelAttribute(): string
    {
        $locale = app()->getLocale();

        $name = $locale === 'ar'
            ? ($this->name_ar ?? $this->name_en ?? ('#'.$this->id))
            : ($this->name_en ?? $this->name_ar ?? ('#'.$this->id));

        $grade = $locale === 'ar' ? optional($this->grade)->name_ar : optional($this->grade)->name_en;
        $semester = $locale === 'ar' ? optional($this->semester)->name_ar : optional($this->semester)->name_en;
        $study = $locale === 'ar' ? optional($this->studyType)->name_ar : optional($this->studyType)->name_en;

        $meta = collect([$grade, $semester, $study])->filter()->implode(' • ');

        return $meta ? "{$name} — {$meta}" : $name;
    }
    //challenges
    public function challenges()
    {
        return $this->hasMany(Challenge::class);
    }

}

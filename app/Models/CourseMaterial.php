<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseMaterial extends Model
{
    use HasFactory;
    protected $fillable = [
        'subject_id',
        'lesson_section_id',
        'name_ar',
        'name_en',
        'duration',
        'duration_text',
        'video',
        'file',
        'status',
        'type',
        'is_free',
        'upload_status',
        'uploaded_by',
        'order_by'
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function section()
    {
        return $this->belongsTo(LessonSection::class, 'lesson_section_id');
    }
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
    }

    //
    public function materialViews()
    {
        return $this->hasMany(MaterialView::class, 'course_material_id');
    }
//
//    public function views()
//    {
//        return $this->hasMany(MaterialView::class, 'course_material_id');
//    }


    public function isViewedBy(int $userId): bool
    {
        return $this->materialViews()
            ->where('user_id', $userId)
            ->where('duration_watched', '>', 0)
            ->exists();
    }

    public function scopeWithIsViewed($query, int $userId)
    {
        return $query->withExists([
            'materialViews as is_viewed' => function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->where('duration_watched', '>', 0);
            }
        ]);
    }
}

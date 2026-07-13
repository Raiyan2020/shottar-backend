<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'name_ar',
        'name_en',
        'status',
        'order_by',
        'challenge_duration',
        'challenge_active',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
    public function challengeQuestions()
    {
        return $this->hasMany(ChallengeQuestion::class, 'lesson_section_id');
    }
    public function courseMaterials()
    {
        return $this->hasMany(CourseMaterial::class, 'lesson_section_id');
    }

    public function challengeUserSessions()
    {
        return $this->hasMany(ChallengeUserSession::class, 'lesson_section_id');
    }



}

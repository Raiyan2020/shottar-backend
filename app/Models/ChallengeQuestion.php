<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_section_id',
        'title_ar',
        'title_en',
    ];

    public function section()
    {
        return $this->belongsTo(LessonSection::class, 'lesson_section_id');
    }
    public function answers()
    {
        return $this->hasMany(ChallengeAnswer::class, 'challenge_question_id');
    }



}

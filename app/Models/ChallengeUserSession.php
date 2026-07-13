<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeUserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'lesson_section_id',
        'started_at',
        'ended_at',
        'score'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
    public function section()
    {
        return $this->belongsTo(LessonSection::class, 'lesson_section_id');
    }

    public function answers() {
        return $this->hasMany(ChallengeUserAnswer::class);
    }





}

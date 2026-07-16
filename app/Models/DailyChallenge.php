<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'grade_id',
        'semester_id',
        'title_ar',
        'title_en',
        'reward_points',
        'time_limit_seconds',
        'challenge_date',
        'status',
    ];

    protected $casts = [
        'challenge_date' => 'date',
        'status' => 'boolean',
        'reward_points' => 'integer',
        'time_limit_seconds' => 'integer',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function options()
    {
        return $this->hasMany(DailyChallengeOption::class)->orderBy('order_by');
    }

    public function userAnswers()
    {
        return $this->hasMany(DailyChallengeUserAnswer::class);
    }

    public function correctOption()
    {
        return $this->hasOne(DailyChallengeOption::class)->where('is_correct', 1);
    }
}

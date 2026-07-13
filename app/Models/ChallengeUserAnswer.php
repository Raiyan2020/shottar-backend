<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeUserAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'challenge_user_session_id',
        'challenge_question_id',
        'challenge_answer_id',
        'is_correct',
    ];

    public function challengeUser()
    {
        return $this->hasMany(ChallengeUserSession::class, 'challenge_user_session_id');
    }
    //question
    public function question()
    {
        return $this->belongsTo(ChallengeQuestion::class, 'challenge_question_id');
    }

    public function answers()
    {
        return $this->belongsTo(ChallengeAnswer::class, 'challenge_answer_id');
    }


}

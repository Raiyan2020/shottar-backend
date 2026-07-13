<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'challenge_question_id',
        'title_ar',
        'title_en',
        'is_correct',
    ];

    public function question()
    {
        return $this->belongsTo(ChallengeQuestion::class, 'challenge_question_id');
    }
}

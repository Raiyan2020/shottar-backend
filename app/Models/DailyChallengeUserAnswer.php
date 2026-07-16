<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyChallengeUserAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_challenge_id',
        'user_id',
        'daily_challenge_option_id',
        'is_correct',
        'earned_points',
        'answered_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'earned_points' => 'integer',
        'answered_at' => 'datetime',
    ];

    public function challenge()
    {
        return $this->belongsTo(DailyChallenge::class, 'daily_challenge_id');
    }

    public function option()
    {
        return $this->belongsTo(DailyChallengeOption::class, 'daily_challenge_option_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

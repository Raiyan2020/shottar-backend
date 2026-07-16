<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyChallengeOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_challenge_id',
        'title_ar',
        'title_en',
        'is_correct',
        'order_by',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'order_by' => 'integer',
    ];

    public function challenge()
    {
        return $this->belongsTo(DailyChallenge::class, 'daily_challenge_id');
    }
}

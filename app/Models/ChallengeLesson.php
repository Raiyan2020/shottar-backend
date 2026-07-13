<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'challenge_id',
        'course_material_id',
        'order_by',
        'duration',
    ];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    public function courseMaterial()
    {
        return $this->belongsTo(CourseMaterial::class);
    }
}

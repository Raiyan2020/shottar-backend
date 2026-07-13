<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_material_id',
        'viewed_at',
        'duration_watched',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function material()
    {
        return $this->belongsTo(CourseMaterial::class, 'course_material_id');
    }




}

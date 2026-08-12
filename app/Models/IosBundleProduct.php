<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IosBundleProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'semester_id',
        'ios_product_id',
    ];

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}

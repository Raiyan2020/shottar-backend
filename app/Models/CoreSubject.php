<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'image',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function getImageAttribute($value): ?string
    {
        return normalize_public_path($value);
    }

    public function setImageAttribute($value): void
    {
        $this->attributes['image'] = normalize_public_path($value);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        if ($locale === 'en') {
            return $this->name_en ?: $this->name_ar;
        }

        return $this->name_ar ?: ($this->name_en ?? '');
    }
}

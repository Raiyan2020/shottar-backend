<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;


    protected $fillable = [
        'name_ar',
        'name_en',
        'status',
        'all_materials_price',
        'icon_number',
        'order_by',
        'discount_2_materials',
        'discount_3_materials',
        'discount_4_materials',
        'discount_5_materials',
        'discount_6_materials',
        'discount_7_materials',
        'discount_all_materials'

    ];

    public function iosBundleProducts()
    {
        return $this->hasMany(IosBundleProduct::class);
    }

    public function iosProductIdFor(?int $semesterId): ?string
    {
        if (! $semesterId) {
            return null;
        }

        if ($this->relationLoaded('iosBundleProducts')) {
            return optional($this->iosBundleProducts->firstWhere('semester_id', $semesterId))->ios_product_id;
        }

        return $this->iosBundleProducts()->where('semester_id', $semesterId)->value('ios_product_id');
    }

    public function getDiscount($materialsCount)
    {
        return match ($materialsCount) {
            2 => $this->discount_2_materials,
            3 => $this->discount_3_materials,
            4 => $this->discount_4_materials,
            5 => $this->discount_5_materials,
            6 => $this->discount_6_materials,
            7 => $this->discount_7_materials,
            default => 0,
        };
    }
}

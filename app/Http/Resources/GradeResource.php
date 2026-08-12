<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    public function __construct($resource, protected ?int $semesterId = null)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang') === 'ar' ? 'ar' : 'en';
        $semesterId = $this->semesterId
            ?? ($request->input('semester_id') ? (int) $request->input('semester_id') : null)
            ?? (auth()->user()?->semester_id ? (int) auth()->user()->semester_id : null);

        return [

            'id' => $this->id,
            'name' => $this->{"name_$lang"},
            'status' => $this->status,
            'all_materials_price' => $this->all_materials_price,
            'icon_number' => $this->icon_number,
            'discount_2_materials' => $this->discount_2_materials,
            'discount_3_materials' => $this->discount_3_materials,
            'discount_4_materials' => $this->discount_4_materials,
            'discount_5_materials' => $this->discount_5_materials,
            'discount_6_materials' => $this->discount_6_materials,
            'discount_7_materials' => $this->discount_7_materials,
            'discount_all_materials' => $this->discount_all_materials,
            'ios_product_id' => $this->iosProductIdFor($semesterId),

        ];
    }
}

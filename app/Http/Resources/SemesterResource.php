<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SemesterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang') === 'ar' ? 'ar' : 'en';
        return [

            'id' => $this->id,
            'name' => $this->{"name_$lang"},
            'status' => $this->status,
        ];
    }
}

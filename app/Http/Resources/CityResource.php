<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang', 'ar');

        return [
            'id'   => $this->id,
            'name' => $lang === 'en' ? $this->name_en : $this->name_ar,
        ];
    }
}

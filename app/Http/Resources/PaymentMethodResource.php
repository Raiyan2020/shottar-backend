<?php

namespace App\Http\Resources;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang', 'ar');

        return [
            'id'    => $this->id,
            'name'  => $lang === 'en' ? $this->name_en : $this->name_ar,
            'slug'  => $this->slug,
            'is_offline' => PaymentMethod::isOffline($this->slug),
            'image' => image_url($this->image),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,
            'name' => $this->name,
            'phone'=> $this->phone,
            'device_type' => $this->device_type,
            'status' => $this->status == 1 ? 'active' : ($this->status == '2' ? 'pending activation':'inactive') ,
            'image' => $this->image ? asset($this->image) : null,
            'country_code' => $this->country_code,
            'phone_not_code' => $this->phone_not_code,
            'language' => $this->language ??'ar',
            'notification_enabled' => $this->notification_enabled == 1 ? true : false,
            //grade
            'grade' => $this->grade_id,
            'semester' => $this->semester_id,
            'last_result' => null,

        ];
    }
}

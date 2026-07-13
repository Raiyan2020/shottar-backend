<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang', 'ar');
        $name = $lang === 'ar' ? $this->name_ar : $this->name_en;
        return [
            'id' => $this->id,
            'title' => $name,
            'file' => url($this->file),
            'type' => 'note',
            'is_free' => $this->is_free ? true : false,
        ];
    }
}

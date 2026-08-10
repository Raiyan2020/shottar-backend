<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang', 'ar');

        return [
            'id' => $this->id,
            'title' => $lang === 'ar' ? $this->name_ar : $this->name_en,
            'file' => stored_file_url($this->file),
            'type' => 'exam',
            'is_free' => (bool) $this->is_free,
        ];
    }
}

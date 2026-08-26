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
            // §6: المسار الموصى بيه للتنزيل (نفس ملاحظة NoteResource).
            'download_url' => route('api.material-file', ['type' => 'exam', 'id' => $this->id]),
            'is_free' => (bool) $this->is_free,
            // الوحدة اللي المرفق تابع لها — null يعني على مستوى المادة كلها.
            'lesson_section_id' => $this->lesson_section_id,
            'lesson_section_name' => optional($this->section)->{"name_$lang"},
        ];
    }
}

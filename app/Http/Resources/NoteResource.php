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
            // كان url() وده بيبني لينك غلط لو المسار مخزّن نسبي — stored_file_url
            // هي نفس الدالة المستخدمة في ExamResource.
            'file' => stored_file_url($this->file),
            'type' => 'note',
            // §6: المسار الموصى بيه للتنزيل — بيرجّع application/pdf و
            // Content-Length صح، و404 JSON لو الملف مش موجود.
            'download_url' => route('api.material-file', ['type' => 'note', 'id' => $this->id]),
            'is_free' => (bool) $this->is_free,
        ];
    }
}

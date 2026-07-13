<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StartChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // السماح للجميع (بما أنهم مستخدمين مصدقين في الـ middleware)
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_id' => 'required|exists:subjects,id',
            'lesson_section_id' => 'required|exists:lesson_sections,id',
        ];
    }

    public function messages(): array
    {
        return [
            'subject_id.required' => 'يجب تحديد المادة.',
            'subject_id.exists' => 'المادة غير موجودة.',
            'lesson_section_id.required' => 'يجب تحديد القسم.',
            'lesson_section_id.exists' => 'القسم غير موجود.',
        ];
    }
}

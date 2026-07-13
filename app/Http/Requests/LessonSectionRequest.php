<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LessonSectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_id' => 'required', 'exists:subjects,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'status' => 'nullable|boolean',
            'challenge_active' => 'nullable|boolean',
            'challenge_duration' => 'nullable',
        ];
    }
}

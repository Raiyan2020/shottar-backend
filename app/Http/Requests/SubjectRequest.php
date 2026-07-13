<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubjectRequest extends FormRequest
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
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH']);
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'grade_id' => 'required|exists:grades,id',
            'study_type_id' => 'nullable|exists:study_types,id',
            'semester_id' => 'required|exists:semesters,id',
            'price' => 'required|numeric|min:0',
            'image' => ($isUpdate ? 'nullable' : 'required') . '|image|max:102400',
            'duration' => 'nullable|string|max:50',
            'status' => 'nullable|boolean',
        ];
    }
}

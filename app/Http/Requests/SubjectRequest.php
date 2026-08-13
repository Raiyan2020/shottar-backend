<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'core_subject_id' => 'required|exists:core_subjects,id',
            'grade_id' => 'required|exists:grades,id',
            'study_type_id' => 'nullable|exists:study_types,id',
            'semester_ids' => 'required|array|min:1',
            'semester_ids.*' => 'integer|exists:semesters,id',
            'price' => 'required|numeric|min:0',
            'duration' => 'nullable|string|max:50',
            'status' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'core_subject_id.required' => __('general.Select Core Subject'),
        ];
    }

    public function attributes(): array
    {
        return [
            'core_subject_id' => __('general.Core Subject'),
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\IosProductIdRule;

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
            'ios_product_id' => $this->iosProductIdRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'core_subject_id.required' => __('general.Select Core Subject'),
            'ios_product_id.regex' => __('general.ios_product_id_invalid'),
            'ios_product_id.max' => __('general.ios_product_id_max'),
        ];
    }

    public function attributes(): array
    {
        return [
            'core_subject_id' => __('general.Core Subject'),
            'ios_product_id' => __('general.ios_product_id'),
        ];
    }

    protected function iosProductIdRules(): array
    {
        $subject = $this->route('subject');
        $subjectId = is_object($subject) ? $subject->id : $subject;
        $current = is_object($subject) ? $subject->ios_product_id : null;
        $locked = IosProductIdRule::isLocked($current) ? $current : null;

        return [
            'nullable',
            'string',
            'max:100',
            new IosProductIdRule(
                ignoreSubjectId: $subjectId ? (int) $subjectId : null,
                lockedOriginal: $locked,
            ),
        ];
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('ios_product_id');
        if (is_string($value)) {
            $this->merge(['ios_product_id' => trim($value) === '' ? null : trim($value)]);
        }
    }
}

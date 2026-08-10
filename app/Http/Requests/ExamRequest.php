<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH'], true);

        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'file' => [$isUpdate ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:102400'],
            'status' => ['nullable', 'boolean'],
            'is_free' => ['nullable', 'boolean'],
        ];
    }
}

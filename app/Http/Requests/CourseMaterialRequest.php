<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseMaterialRequest extends FormRequest
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
            'subject_id'         => ['required', 'exists:subjects,id'],
            // لازم الوحدة تكون تابعة لنفس المادة (subject_id) اللي جايه في
            // الفورم، مش أي وحدة في أي مادة تانية — عشان دلوقتي المدرس بقى
            // يقدر يختار الوحدة بنفسه (مش مقفولة زي الأول).
            'lesson_section_id'  => [
                'required',
                Rule::exists('lesson_sections', 'id')->where('subject_id', $this->input('subject_id')),
            ],
            'name_ar'            => ['required', 'string', 'max:255'],
            'name_en'            => ['required', 'string', 'max:255'],
            'duration'           => ['nullable', 'string', 'max:100'],
//            'video'             => ['nullable', 'file', 'max:20480'], // 20MB
            'video'                 => ['nullable', 'url'],
            //vimeo_uri
            'vimeo_uri'          => ['nullable', 'string', 'max:255'],
            // file 100 MB
            'file'               => ['nullable', 'file', 'max:102400'], // 100MB
            'status'             => ['nullable', 'boolean'],
            'type'               => ['required', 'in:lesson,note'],
            'is_free'            => ['nullable', 'boolean'],
        ];
    }
}

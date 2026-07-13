<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true; // Change to false if you want to restrict access
    }

    public function rules()
    {
        $id = $this->route('teacher') ?? $this->route('id');

        return [
            'name'      => ['required','string','max:255'],
            'email'     => ['required','email','max:255', Rule::unique('admins','email')->ignore($id)],
            'password'  => ['nullable','min:6'],
            'photo'     => ['nullable','image','max:102400'],

            'subject_ids'   => ['required','array','min:1'],
            'subject_ids.*' => ['integer','exists:subjects,id'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => trans('validation.required', ['attribute' => trans('validation.attributes.name')]),
            'name_ar.required' => trans('validation.required', ['attribute' => trans('validation.attributes.name_ar')]),
            'email.required' => trans('validation.required', ['attribute' => trans('validation.attributes.email')]),
            'email.email' => trans('validation.email'),
            'email.unique' => trans('validation.unique', ['attribute' => trans('validation.attributes.email')]),
            'password.required' => trans('validation.required', ['attribute' => trans('validation.attributes.password')]),
            'password.min' => trans('validation.min', ['attribute' => trans('validation.attributes.password'), 'min' => 8]),
            'password.confirmed' => trans('validation.confirmed', ['attribute' => trans('validation.attributes.password')]),
            'photo.image' => trans('validation.image', ['attribute' => trans('validation.attributes.photo')]),
            'photo.mimes' => trans('validation.mimes', ['attribute' => trans('validation.attributes.photo')]),
            'photo.max' => trans('validation.max', ['attribute' => trans('validation.attributes.photo'), 'max' => 2]),
        ];
    }

}

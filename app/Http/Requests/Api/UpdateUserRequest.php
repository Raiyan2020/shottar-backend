<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        $userId = Auth::id();
        if ($this->has('country_code') && $this->has('phone')) {
            $this->merge(['phone_not_code' => $this->phone]);
            $this->merge(['phone' => $this->country_code . $this->phone]);
        }
        return [
            'name'  => ['required', 'string', 'max:255'],
            'phone'      => ['required', 'string', 'max:20', "unique:users,phone,{$userId}"],
            'phone_not_code' => ['nullable', 'string', 'max:20'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:102400'], // 100MB max
            'country_code' => ['required', 'string', 'max:5'],
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
        ];
    }
}

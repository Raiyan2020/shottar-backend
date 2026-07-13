<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
        if ($this->has('country_code') && $this->has('phone')) {
            $this->merge(['phone_not_code' => $this->phone]);
            $this->merge(['phone' => $this->country_code . $this->phone]);
        }
        return [
            'phone' => 'required|string|min:8|max:15',
            'device_type' => 'nullable|string|in:ios,android',
            'device_token' => 'nullable|string',
            'country_code' => 'required|string|min:1|max:5',
            'phone_not_code' => 'nullable|string|max:20',
        ];
    }
}

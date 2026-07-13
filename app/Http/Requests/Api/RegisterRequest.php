<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true; // تأكد من السماح للجميع بالوصول لهذا الطلب
    }

    public function rules()
    {
        if ($this->has('country_code') && $this->has('phone')) {
            $this->merge(['phone_not_code' => $this->phone]);
            $this->merge(['phone' => $this->country_code . $this->phone]);
        }
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'device_token' => 'nullable|string',
            'device_type' => 'nullable|string|in:ios,android',
            'country_code' => 'required|string|max:5',
            'phone_not_code' => 'nullable|string|max:20',

        ];
    }

    public function messages()
    {
        return [
            'phone.unique' => $this->header('lang') == 'ar' ? 'الرقم موجود بالفعل' : 'Phone already exists',
        ];
    }
}


<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
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
    public function rules()
    {
        // اسم الباراميتر في الراوت هو {payment_method} مش {id}. كان الكود بيقرأ
        // route('id') وبيرجع null، فقاعدة unique كانت بتقارن السجل بنفسه وترمي
        // "The slug must be unique." وقت أي تعديل — حتى لو مغيرتش الـ slug أصلاً.
        return [
            'name_ar' => 'nullable|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('payment_methods', 'slug')->ignore($this->route('payment_method')),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:102400',
            'status' => 'nullable|boolean',
        ];
    }
}

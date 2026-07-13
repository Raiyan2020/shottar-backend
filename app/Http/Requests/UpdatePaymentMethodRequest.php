<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        $paymentMethodId = $this->route('id'); // أو 'paymentMethod' حسب اسم الباراميتر في الراوت

        return [
            'name_ar' => 'nullable|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:payment_methods,slug,' . $paymentMethodId,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:102400',
            'status' => 'nullable|boolean',
        ];
    }
}

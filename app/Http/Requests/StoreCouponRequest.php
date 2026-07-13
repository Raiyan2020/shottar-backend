<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
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
            'code'         => 'required|string|unique:coupons,code',
            'type'         => 'required|in:fixed,percent',
            'value'        => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit'  => 'nullable|integer|min:1',
            'starts_at'    => 'nullable|date',
            'expires_at'   => 'nullable|date|after_or_equal:starts_at',
            'status'       => 'boolean',
        ];

    }
}

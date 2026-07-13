<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradeRequest extends FormRequest
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
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'all_materials_price' => 'nullable|numeric|min:0',
            'icon_number' => 'required|integer|min:0',
            'status' => 'required|boolean',
            'order_by' => 'nullable|integer|min:0',
            'discount_2_materials' => 'required|numeric|min:0|max:100',
            'discount_3_materials' => 'required|numeric|min:0|max:100',
            'discount_4_materials' => 'required|numeric|min:0|max:100',
            'discount_5_materials' => 'required|numeric|min:0|max:100',
            'discount_6_materials' => 'required|numeric|min:0|max:100',
            'discount_7_materials' => 'required|numeric|min:0|max:100',
            'discount_all_materials' => 'required|numeric|min:0|max:100',

        ];
    }
}

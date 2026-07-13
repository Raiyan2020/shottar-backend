<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'total' => 'required|numeric|min:0',
            'is_all_materials' => 'required|boolean',
            'items' => 'required|array',
            'items.*' => 'required|exists:subjects,id',
        ];
    }
    protected function prepareForValidation()
    {
        if (is_string($this->items)) {
            $items = array_map('intval', explode(',', $this->items));
            $this->merge([
                'items' => $items,
            ]);
        }
    }
}

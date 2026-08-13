<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH']);

        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'image' => ($isUpdate ? 'nullable' : 'required') . '|image|max:102400',
            'status' => 'nullable|boolean',
        ];
    }
}

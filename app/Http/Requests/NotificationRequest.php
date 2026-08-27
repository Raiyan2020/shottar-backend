<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'send_type' => 'required|string|in:all,one,group,unpaid',
            'user_id' => [Rule::requiredIf($this->input('send_type') === 'one'), 'nullable', 'exists:users,id'],
            'users' => [Rule::requiredIf($this->input('send_type') === 'group'), 'nullable', 'array', 'min:1'],
            'users.*' => 'exists:users,id',
        ];
    }
}

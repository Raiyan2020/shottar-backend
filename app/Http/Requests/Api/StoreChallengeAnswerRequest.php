<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreChallengeAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // السماح للجميع (سيتم ضبط الأمان من الـ middleware)
    }

    public function rules(): array
    {
        return [
            'session_id' => 'required|exists:challenge_user_sessions,id',
            'question_id' => 'required|exists:challenge_questions,id',
            'answer_id' => 'required|exists:challenge_answers,id',
        ];
    }

    public function messages(): array
    {
        return [
            'session_id.required' => 'رقم الجلسة مطلوب.',
            'session_id.exists' => 'الجلسة غير موجودة.',
            'question_id.required' => 'رقم السؤال مطلوب.',
            'question_id.exists' => 'السؤال غير موجود.',
            'answer_id.required' => 'رقم الإجابة مطلوب.',
            'answer_id.exists' => 'الإجابة غير موجودة.',
        ];
    }
}

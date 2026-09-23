<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreChallengeQuestionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'answers' => 'required|array|min:2',
            'answers.*.title_ar' => 'required|string|max:255',
            'answers.*.title_en' => 'nullable|string|max:255',
            'correct_answer' => 'required',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            $answers = (array) $this->input('answers', []);
            $correctAnswer = (string) $this->input('correct_answer');
            $answerKeys = array_map('strval', array_keys($answers));

            if (! in_array($correctAnswer, $answerKeys, true)) {
                $validator->errors()->add('correct_answer', __('general.Please select the correct answer.'));
            }
        });
    }
}

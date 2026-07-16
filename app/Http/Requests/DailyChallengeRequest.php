<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DailyChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $challenge = $this->route('daily_challenge');
        $challengeId = is_object($challenge) ? $challenge->id : $challenge;

        return [
            'grade_id' => 'required|exists:grades,id',
            'semester_id' => 'required|exists:semesters,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'reward_points' => 'required|integer|min:0',
            'time_limit_seconds' => 'required|integer|min:5',
            'challenge_date' => [
                'required',
                'date',
                Rule::unique('daily_challenges', 'challenge_date')
                    ->where(fn ($query) => $query
                        ->where('grade_id', $this->input('grade_id'))
                        ->where('semester_id', $this->input('semester_id')))
                    ->ignore($challengeId),
            ],
            'status' => 'nullable|boolean',
            'options' => 'required|array|size:4',
            'options.*.title_ar' => 'required|string|max:255',
            'options.*.title_en' => 'nullable|string|max:255',
            'correct_answer' => 'required|integer|min:0|max:3',
        ];
    }

    public function messages(): array
    {
        return [
            'challenge_date.unique' => __('general.A daily challenge already exists for this grade, semester and date.'),
            'correct_answer.required' => __('general.Please select the correct answer.'),
            'options.size' => __('general.You must provide exactly 4 options.'),
        ];
    }
}

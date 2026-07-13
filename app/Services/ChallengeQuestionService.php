<?php

namespace App\Services;

use App\Models\ChallengeQuestion;
use App\Models\ChallengeAnswer;

class ChallengeQuestionService
{
    /**
     * إنشاء سؤال جديد
     */
    public function createChallengeQuestion(array $data): ChallengeQuestion
    {
        return ChallengeQuestion::create([
            'lesson_section_id' => $data['lesson_section_id'],
            'title_ar' => $data['title_ar'] ?? null,
            'title_en' => $data['title_en'] ?? null,
        ]);
    }

    /**
     * إنشاء إجابة مرتبطة بسؤال
     */
    public function createChallengeAnswer(array $data): ChallengeAnswer
    {
        return ChallengeAnswer::create([
            'challenge_question_id' => $data['challenge_question_id'],
            'title_ar' => $data['title_ar'] ?? null,
            'title_en' => $data['title_en'] ?? null,
            'is_correct' => $data['is_correct'] ?? 0,
        ]);
    }

    /**
     * تحديث سؤال وإجاباته
     */
    public function updateChallengeQuestion(ChallengeQuestion $question, array $data)
    {
        $question->update([
            'title_ar' => $data['title_ar'] ?? $question->title_ar,
            'title_en' => $data['title_en'] ?? $question->title_en,
        ]);

        if (isset($data['answers'])) {
            $correctIndex = $data['correct_answer'] ?? null;
            $question->answers()->delete(); // حذف القديم

            foreach ($data['answers'] as $index => $answerData) {
                $answerData['challenge_question_id'] = $question->id;
                $answerData['is_correct'] = ($index == $correctIndex) ? 1 : 0;
                $this->createChallengeAnswer($answerData);
            }
        }

        return $question;
    }

    /**
     * حذف سؤال مع جميع الإجابات
     */
    public function deleteChallengeQuestion(ChallengeQuestion $question)
    {
        $question->answers()->delete();
        $question->delete();
    }
}

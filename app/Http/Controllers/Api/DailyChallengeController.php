<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyChallenge;
use App\Models\DailyChallengeUserAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DailyChallengeController extends Controller
{
    /**
     * GET /daily-challenge — تحدي اليوم للصف/الترم الخاص بالمستخدم.
     */
    public function show(Request $request)
    {
        $user = auth()->user();
        $gradeId = $request->input('grade_id', $user->grade_id);
        $semesterId = $request->input('semester_id', $user->semester_id);

        if (! $gradeId || ! $semesterId) {
            return sendError('Both grade_id and semester_id are required.');
        }

        $challenge = DailyChallenge::with(['options', 'subject'])
            ->where('status', 1)
            ->where('grade_id', $gradeId)
            ->where('semester_id', $semesterId)
            ->whereDate('challenge_date', Carbon::today())
            ->latest('id')
            ->first();

        if (! $challenge) {
            return sendResponse(null);
        }

        $answer = DailyChallengeUserAnswer::where('daily_challenge_id', $challenge->id)
            ->where('user_id', $user->id)
            ->first();

        return sendResponse($this->formatChallenge($request, $challenge, $answer));
    }

    /**
     * POST /daily-challenge/answer — إرسال إجابة التحدي.
     */
    public function answer(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'challenge_id' => 'required|integer|exists:daily_challenges,id',
            'question_id' => 'nullable|integer',
            'option_id' => 'required|integer|exists:daily_challenge_options,id',
        ]);

        if ($validator->fails()) {
            return sendError($validator->errors()->first());
        }

        $challenge = DailyChallenge::with('options')->findOrFail($request->challenge_id);

        $option = $challenge->options->firstWhere('id', (int) $request->option_id);
        if (! $option) {
            return sendError('The selected option does not belong to this challenge.');
        }

        // منع الإجابة أكثر من مرة على نفس التحدي
        $existing = DailyChallengeUserAnswer::where('daily_challenge_id', $challenge->id)
            ->where('user_id', $user->id)
            ->first();
        if ($existing) {
            return sendError('You have already answered this challenge.', [], 409);
        }

        $isCorrect = (bool) $option->is_correct;
        $earnedPoints = $isCorrect ? (int) $challenge->reward_points : 0;
        $correctOptionId = optional($challenge->options->firstWhere('is_correct', true))->id;

        $totalPoints = DB::transaction(function () use ($challenge, $user, $option, $isCorrect, $earnedPoints) {
            DailyChallengeUserAnswer::create([
                'daily_challenge_id' => $challenge->id,
                'user_id' => $user->id,
                'daily_challenge_option_id' => $option->id,
                'is_correct' => $isCorrect,
                'earned_points' => $earnedPoints,
                'answered_at' => now(),
            ]);

            $user->increment('points', $earnedPoints);
            $user->increment('daily_goal_done');

            return (int) $user->refresh()->points;
        });

        return sendResponse([
            'is_correct' => $isCorrect,
            'selected_option_id' => $option->id,
            'correct_option_id' => $correctOptionId,
            'earned_points' => $earnedPoints,
            'total_points' => $totalPoints,
            'next_challenge_at' => $this->nextChallengeAt(),
        ]);
    }

    /**
     * GET /daily-challenge/history — سجل التحديات المُجابة.
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        $gradeId = $request->input('grade_id', $user->grade_id);
        $semesterId = $request->input('semester_id', $user->semester_id);

        $lang = $request->header('lang', 'ar');

        $query = DailyChallengeUserAnswer::with(['challenge.subject'])
            ->where('user_id', $user->id);

        if ($gradeId && $semesterId) {
            $query->whereHas('challenge', function ($q) use ($gradeId, $semesterId) {
                $q->where('grade_id', $gradeId)->where('semester_id', $semesterId);
            });
        }

        $answers = $query->orderByDesc('id')->get();

        $data = $answers->map(function ($answer) use ($lang) {
            $challenge = $answer->challenge;

            return [
                'id' => $answer->id,
                'date' => optional($challenge?->challenge_date)->toDateString(),
                'subject_name' => $this->subjectName($challenge?->subject, $lang),
                'question' => $challenge
                    ? ($lang === 'en' ? ($challenge->title_en ?: $challenge->title_ar) : $challenge->title_ar)
                    : null,
                'is_correct' => (bool) $answer->is_correct,
                'earned_points' => (int) $answer->earned_points,
            ];
        })->values();

        return sendResponse($data);
    }

    /**
     * يبني شكل التحدي المتوقع من التطبيق.
     */
    protected function formatChallenge(Request $request, DailyChallenge $challenge, ?DailyChallengeUserAnswer $answer): array
    {
        $lang = $request->header('lang', 'ar');
        $answered = (bool) $answer;

        $result = null;
        if ($answered) {
            $correctOptionId = optional($challenge->options->firstWhere('is_correct', true))->id;
            $result = [
                'is_correct' => (bool) $answer->is_correct,
                'selected_option_id' => $answer->daily_challenge_option_id,
                'correct_option_id' => $correctOptionId,
                'earned_points' => (int) $answer->earned_points,
                'total_points' => (int) ($request->user()->points ?? 0),
                'next_challenge_at' => $this->nextChallengeAt(),
            ];
        }

        return [
            'id' => $challenge->id,
            'subject_id' => $challenge->subject_id,
            'subject_name' => $this->subjectName($challenge->subject, $lang),
            'reward_points' => (int) $challenge->reward_points,
            'time_limit_seconds' => (int) $challenge->time_limit_seconds,
            'next_challenge_at' => $this->nextChallengeAt(),
            'answered' => $answered,
            'question' => [
                'id' => $challenge->id,
                'title' => $lang === 'en' ? ($challenge->title_en ?: $challenge->title_ar) : $challenge->title_ar,
                'options' => $challenge->options->map(function ($option) use ($lang) {
                    return [
                        'id' => $option->id,
                        'title' => $lang === 'en' ? ($option->title_en ?: $option->title_ar) : $option->title_ar,
                    ];
                })->values(),
            ],
            'result' => $result,
        ];
    }

    protected function subjectName($subject, string $lang): ?string
    {
        if (! $subject) {
            return null;
        }

        return $lang === 'en' ? ($subject->name_en ?: $subject->name_ar) : $subject->name_ar;
    }

    protected function nextChallengeAt(): string
    {
        return Carbon::tomorrow()->startOfDay()->toISOString();
    }
}

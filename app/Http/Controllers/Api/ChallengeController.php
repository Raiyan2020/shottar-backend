<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StartChallengeRequest;
use App\Http\Requests\Api\StoreChallengeAnswerRequest;
use App\Http\Resources\ChallengeHistoryResource;
use App\Http\Resources\LessonSectionChallengeResource;
use App\Models\ChallengeAnswer;
use App\Models\ChallengeQuestion;
use App\Models\ChallengeUserAnswer;
use App\Models\ChallengeUserSession;
use App\Models\LessonSection;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    //index
    public function ChallengeSubjects($subject_id){
        $sections = LessonSection::withCount('challengeQuestions')
            ->where('subject_id', $subject_id)
            ->where('challenge_active', 1)
            ->has('challengeQuestions')
            ->get();

        return sendResponse(LessonSectionChallengeResource::collection($sections));

    }
    //startChallenge
    public function startChallenge(StartChallengeRequest  $request)
    {
        $user = auth('api')->user();

        $section = LessonSection::where('id', $request->lesson_section_id)
            ->where('challenge_active', 1)->firstOrFail();

        // تأكد أن الطالب أكمل جميع الدروس في القسم
        $totalLessons = $section->courseMaterials()
            ->where('type', 1)
            ->count();

        $viewedLessons = $section->courseMaterials()
            ->where('type', 1)
            ->whereHas('materialViews', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->count();

        if ($totalLessons > 0 && $viewedLessons < $totalLessons) {
            return sendError( 'يجب إكمال جميع الدروس قبل بدء التحدي.');
        }

        // إنشاء جلسة جديدة
        $session = ChallengeUserSession::create([
            'user_id' => $user->id,
            'subject_id' => $request->subject_id,
            'lesson_section_id' => $request->lesson_section_id,
            'started_at' => now(),

        ]);

        $lang =$request->header('lang');
        $title = $lang == 'ar' ? 'title_ar' : 'title_en';
        $questions = $section->challengeQuestions()
            ->with(['answers' => function ($q) use ($title) {
                $q->select('id', 'challenge_question_id', $title, 'is_correct');
            }])
            ->select('id', 'lesson_section_id', $title)
            ->get();
        $data = [
            'session_id' => $session->id,
            'started_at' => $session->started_at,
            'challenge_duration' => $section->challenge_duration, // مدة الاختبار (مثلاً بالدقائق)
            'questions' => $questions,
        ];
        return sendResponse($data,'تم بدء التحدي بنجاح');

    }

    public function storeAnswer(StoreChallengeAnswerRequest $request)
    {
        $user = auth('api')->user();


        // جلب الإجابة للتحقق من صحتها
        $answer = ChallengeAnswer::where('id', $request->answer_id)
            ->where('challenge_question_id', $request->question_id)
            ->firstOrFail();

        // التأكد أن الجلسة تخص نفس المستخدم
        $session = ChallengeUserSession::where('id', $request->session_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // تحديد إذا كانت صحيحة أو لا
        $isCorrect = $answer->is_correct ? 1 : 0;

        // تخزين الإجابة (تحديث إذا كانت موجودة لنفس السؤال)
        $userAnswer = ChallengeUserAnswer::updateOrCreate(
            [
                'challenge_user_session_id' => $session->id,
                'challenge_question_id' => $request->question_id,
            ],
            [
                'challenge_answer_id' => $request->answer_id,
                'is_correct' => $isCorrect,
            ]
        );

        return sendResponse([
            'question_id' => $request->question_id,
            'is_correct' => $isCorrect,
        ], 'تم حفظ الإجابة بنجاح');
    }

    public function finishChallenge(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'session_id' => 'required|exists:challenge_user_sessions,id',
        ]);

        $session = ChallengeUserSession::where('id', $request->session_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // احسب عدد الأسئلة الكلي
        $totalQuestions = ChallengeQuestion::where('lesson_section_id', $session->lesson_section_id)->count();

        // احسب عدد الإجابات الصحيحة
        $correctAnswers = ChallengeUserAnswer::where('challenge_user_session_id', $session->id)
            ->where('is_correct', 1)
            ->count();

        // احسب النسبة المئوية
        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        // حدث الجلسة
        $session->update([
            'ended_at' => now(),
            'score' => $score,
        ]);

        // جلب تفاصيل الأسئلة والإجابات
        $lang =$request->header('lang');
        $title = $lang == 'ar' ? 'title_ar' : 'title_en';
        $questions = ChallengeQuestion::with([
            'answers:id,challenge_question_id,title_ar,title_en,is_correct',
        ])
            ->where('lesson_section_id', $session->lesson_section_id)
            ->get()
            ->map(function ($q) use ($session ,$title) {
                $userAnswer = ChallengeUserAnswer::where('challenge_user_session_id', $session->id)
                    ->where('challenge_question_id', $q->id)
                    ->first();

                return [
                    'question_id' => $q->id,
                    'question' => $q->$title,
                    'answers' => $q->answers->map(function ($a) use ($title) {
                        return [
                            'id' => $a->id,
                            'text' => $a->$title,
                            'is_correct' => (bool)$a->is_correct,
                        ];
                    }),
                    'user_answer_id' => $userAnswer?->challenge_answer_id,
                    'user_is_correct' => (bool)($userAnswer?->is_correct ?? false),
                    'correct_answer_id' => $q->answers->where('is_correct', 1)->first()?->id,
                ];
            });

        return sendResponse([
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'score' => $score,
            'questions' => $questions,
        ], 'تم إنهاء التحدي بنجاح');
    }



    public function previousChallenges(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
        ]);

        // جلب الأقسام الخاصة بالمادة
//        $sections = LessonSection::where('subject_id', $request->subject_id)
//            ->where('challenge_active', 1)
//            ->with(['challengeUserSessions' => function ($q) use ($user) {
//                $q->where('user_id', $user->id)->latest()->limit(1);
//            }])
//            ->get();
        $sessions = ChallengeUserSession::where('user_id', $user->id)
            ->where('subject_id', $request->subject_id)
            ->with([
                'section:id,name_ar,name_en,challenge_duration',
                'section.challengeQuestions:id,lesson_section_id',
            ])
            ->latest()
            ->get();
        if ($sessions->isEmpty()) {
            return sendResponse([], 'لا توجد تحديات سابقة لهذا المستخدم.');
        }



        return sendResponse(ChallengeHistoryResource::collection($sessions), 'تم جلب التحديات السابقة بنجاح');
    }
    public function showChallengeResult(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'session_id' => 'required|exists:challenge_user_sessions,id',
        ]);

        $session = ChallengeUserSession::where('id', $request->session_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // جلب تفاصيل الأسئلة والإجابات
        $lang = $request->header('lang');
        $title = $lang == 'ar' ? 'title_ar' : 'title_en';

        $questions = ChallengeQuestion::with([
            'answers:id,challenge_question_id,title_ar,title_en,is_correct',
        ])
            ->where('lesson_section_id', $session->lesson_section_id)
            ->get()
            ->map(function ($q) use ($session, $title) {
                $userAnswer = ChallengeUserAnswer::where('challenge_user_session_id', $session->id)
                    ->where('challenge_question_id', $q->id)
                    ->first();

                return [
                    'question_id' => $q->id,
                    'question' => $q->$title,
                    'answers' => $q->answers->map(function ($a) use ($title) {
                        return [
                            'id' => $a->id,
                            'text' => $a->$title,
                            'is_correct' => (bool)$a->is_correct,
                        ];
                    }),
                    'user_answer_id' => $userAnswer?->challenge_answer_id,
                    'user_is_correct' => (bool)($userAnswer?->is_correct ?? false),
                    'correct_answer_id' => $q->answers->where('is_correct', 1)->first()?->id,
                ];
            });

        return sendResponse([
            'total_questions' =>
                ChallengeQuestion::where('lesson_section_id', $session->lesson_section_id)->count(),

            'correct_answers' => ChallengeUserAnswer::where('challenge_user_session_id', $session->id)
                ->where('is_correct', 1)
                ->count(),
            'score' => $session->score ?? 0,
            'questions' => $questions,
        ], 'تم جلب تفاصيل التحدي بنجاح');
    }






}

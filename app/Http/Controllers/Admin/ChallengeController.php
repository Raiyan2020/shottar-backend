<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DataTables\ChallengeQuestionDataTable;
use App\Http\Requests\StoreChallengeQuestionRequest;
use App\Http\Requests\UpdateChallengeQuestionRequest;
use App\Models\ChallengeQuestion;
use App\Models\LessonSection;
use App\Services\ChallengeQuestionService;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    protected $challengeQuestionService;

    public function __construct(ChallengeQuestionService $challengeQuestionService)
    {
        $this->challengeQuestionService = $challengeQuestionService;
    }

    /**
     * عرض جميع التحديات التابعة لقسم محدد
     */
    public function index($subject, $section, ChallengeQuestionDataTable $dataTable)
    {
        $lessonSection = LessonSection::findOrFail($section);

        return $dataTable->with(['lesson_section_id' => $lessonSection->id])
            ->render('dashboard.admin.challenges.index', compact('lessonSection'));
    }

    /**
     * صفحة إنشاء تحدي جديد
     */
    public function create($subject, $section)
    {
        $lessonSection = LessonSection::findOrFail($section);
        return view('dashboard.admin.challenges.create', compact('lessonSection'));
    }

    /**
     * تخزين تحدي جديد
     */
    public function store(StoreChallengeQuestionRequest $request, $subject, $section)
    {
        // بيانات السؤال
        $data = $request->validated();
        $data['lesson_section_id'] = $section;

        // أنشئ السؤال أولًا
        $challengeQuestion = $this->challengeQuestionService->createChallengeQuestion($data);

        // الإجابة الصحيحة (index من الـradio)
        $correctIndex = $request->input('correct_answer');

        // إجابات السؤال
        $answers = $request->input('answers', []);

        foreach ($answers as $index => $answerData) {
            $answerData['challenge_question_id'] = $challengeQuestion->id;
            $answerData['is_correct'] = ($index == $correctIndex) ? 1 : 0;

            $this->challengeQuestionService->createChallengeAnswer($answerData);
        }

        return redirect()->route(panelPrefix().'.subjects.sections.challenges.index', [
            'subject' => $subject,
            'section' => $section
        ])->with('success', 'created successfully');
    }


    /**
     * تعديل التحدي
     */
    public function edit($subject, $section, $id)
    {
        $challengeQuestion = ChallengeQuestion::findOrFail($id);
        $lessonSection = LessonSection::findOrFail($section);

        // جلب الإجابات المرتبطة، إذا لم توجد فتعطي مصفوفة فارغة
        $challengeAnswers = $challengeQuestion->answers ?? collect(); // Laravel Collection فارغة

        return view('dashboard.admin.challenges.edit', compact('challengeQuestion', 'lessonSection', 'challengeAnswers'));
    }

    /**
     * تحديث التحدي
     */
    public function update(UpdateChallengeQuestionRequest $request, $subject, $section, $id)
    {
        $data = $request->validated();
        $data['lesson_section_id'] = $section;

        $question = ChallengeQuestion::findOrFail($id);
        $this->challengeQuestionService->updateChallengeQuestion($question, $data);

        $answers = $request->input('answers', []);
        $correctValue = $request->input('correct_answer');

        $existingIds = [];

        foreach ($answers as $index => $answerData) {
            if (isset($answerData['id']) && $answerData['id']) {
                // تحديث الإجابة القديمة
                $answer = $question->answers()->find($answerData['id']);
                if ($answer) {
                    $answer->update([
                        'title_ar' => $answerData['title_ar'],
                        'title_en' => $answerData['title_en'],
                        'is_correct' => ($index == $correctValue) ? 1 : 0,
                    ]);
                    $existingIds[] = $answer->id;
                }
            } else {
                // إنشاء إجابة جديدة
                $newAnswer = $question->answers()->create([
                    'title_ar' => $answerData['title_ar'],
                    'title_en' => $answerData['title_en'],
                    'is_correct' => ($index == $correctValue) ? 1 : 0,
                ]);
                $existingIds[] = $newAnswer->id;
            }
        }

        // حذف الإجابات التي لم تعد موجودة
        $question->answers()->whereNotIn('id', $existingIds)->delete();

        return redirect()->route(panelPrefix().'.subjects.sections.challenges.index', [
            'subject' => $subject,
            'section' => $section
        ])->with('success', 'updated successfully');
    }


    /**
     * حذف التحدي
     */
    public function destroy($subject, $section, $id)
    {
        $challengeQuestion = ChallengeQuestion::findOrFail($id);
        $this->challengeQuestionService->deleteChallengeQuestion($challengeQuestion);
        return response()->json('success');
    }
}

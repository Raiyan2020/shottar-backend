<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\DailyChallengeDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\DailyChallengeRequest;
use App\Models\DailyChallenge;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Subject;
use App\Traits\HasStatusToggle;
use Illuminate\Support\Facades\DB;

class DailyChallengeController extends Controller
{
    use HasStatusToggle;

    public function index(DailyChallengeDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.daily-challenges.index');
    }

    public function create()
    {
        $grades = Grade::where('status', 1)->get();
        $semesters = Semester::where('status', 1)->get();
        $subjects = Subject::where('status', 1)->get();

        return view('dashboard.admin.daily-challenges.create', compact('grades', 'semesters', 'subjects'));
    }

    public function store(DailyChallengeRequest $request)
    {
        $this->save($request);

        return redirect()->route('admin.daily-challenges.index')
            ->with('success', __('general.Daily challenge created successfully'));
    }

    public function edit(DailyChallenge $dailyChallenge)
    {
        $dailyChallenge->load('options');
        $grades = Grade::where('status', 1)->get();
        $semesters = Semester::where('status', 1)->get();
        $subjects = Subject::where('status', 1)->get();

        return view('dashboard.admin.daily-challenges.edit', compact('dailyChallenge', 'grades', 'semesters', 'subjects'));
    }

    public function update(DailyChallengeRequest $request, DailyChallenge $dailyChallenge)
    {
        $this->save($request, $dailyChallenge);

        return redirect()->route('admin.daily-challenges.index')
            ->with('success', __('general.Daily challenge updated successfully'));
    }

    public function destroy(DailyChallenge $dailyChallenge)
    {
        $dailyChallenge->delete();

        return response()->json('success');
    }

    public function toggleStatus($id)
    {
        return $this->toggleStatu(DailyChallenge::class, $id);
    }

    /**
     * إنشاء أو تحديث التحدي مع خياراته الأربعة داخل معاملة واحدة.
     */
    protected function save(DailyChallengeRequest $request, ?DailyChallenge $challenge = null): DailyChallenge
    {
        $data = $request->validated();
        $options = $data['options'];
        $correctIndex = (int) $data['correct_answer'];
        unset($data['options'], $data['correct_answer']);
        $data['status'] = $request->boolean('status');

        return DB::transaction(function () use ($data, $options, $correctIndex, $challenge) {
            if ($challenge) {
                $challenge->update($data);
                $challenge->options()->delete();
            } else {
                $challenge = DailyChallenge::create($data);
            }

            foreach ($options as $index => $option) {
                $challenge->options()->create([
                    'title_ar' => $option['title_ar'],
                    'title_en' => $option['title_en'] ?? null,
                    'is_correct' => $index === $correctIndex ? 1 : 0,
                    'order_by' => $index,
                ]);
            }

            return $challenge;
        });
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\LessonSectionDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\LessonSectionRequest;
use App\Models\LessonSection;
use App\Models\Subject;
use App\Traits\HasStatusToggle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonSectionController extends Controller
{
    use HasStatusToggle;

    public function index(LessonSectionDataTable $dataTable, Subject $subject)
    {
        $this->authorizeTeacherSubject($subject);

        return $dataTable->with('subject', $subject)->render('dashboard.admin.lesson_sections.index', [
            'subject' => $subject,
        ]);
    }

    public function create(Subject $subject)
    {
        return view('dashboard.admin.lesson_sections.create', compact('subject'));
    }

    public function store(LessonSectionRequest $request, Subject $subject)
    {

        // كان بيحسب order_by في $data وبعدين بيعمل create($request->validated())
        // فالقيمة المحسوبة كانت بتتضيع وكل وحدة جديدة تتولد بـ order_by = 0.
        $maxOrder = (int) $subject->lessonSections()->max('order_by');
        $data = $request->validated();
        $data['order_by'] = $maxOrder + 1;
        $subject->lessonSections()->create($data);

        return redirect()->route(panelPrefix().'.subjects.sections.index', $subject->id)
            ->with('success', __('Section created successfully'));
    }

    public function edit(Subject $subject, LessonSection $section)
    {
        return view('dashboard.admin.lesson_sections.edit', compact('subject', 'section'));
    }

    public function update(LessonSectionRequest $request, Subject $subject, LessonSection $section)
    {
        $section->update($request->validated());

        return redirect()->route(panelPrefix().'.subjects.sections.index', $subject->id)
            ->with('success', __('Section updated successfully'));
    }

    public function destroy(Subject $subject, LessonSection $section)
    {
        $section->delete();

        return response()->json(['status' => true]);
    }

    public function toggleStatus($sectionId)
    {
        return $this->toggleStatu(LessonSection::class, $sectionId);
    }

    public function sort(Request $request, Subject $subject)
    {
        $this->authorizeTeacherSubject($subject);

        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*.id' => ['required', 'integer', 'distinct'],
        ]);

        $sectionIds = collect($data['order'])->pluck('id');
        $ownedSectionsCount = LessonSection::query()
            ->where('subject_id', $subject->id)
            ->whereIn('id', $sectionIds)
            ->count();

        abort_unless($ownedSectionsCount === $sectionIds->count(), 422, __('Invalid section order.'));

        DB::transaction(function () use ($data, $subject) {
            foreach ($data['order'] as $index => $item) {
                LessonSection::query()
                    ->where('subject_id', $subject->id)
                    ->whereKey($item['id'])
                    ->update(['order_by' => $index + 1]);
            }
        });

        return response()->json(['status' => 'success']);
    }

    private function authorizeTeacherSubject(Subject $subject): void
    {
        $user = auth('admin')->user();

        if ($user?->hasRole('teacher')) {
            abort_unless(
                $subject->teachers()->whereKey($user->id)->exists(),
                403
            );
        }
    }
}

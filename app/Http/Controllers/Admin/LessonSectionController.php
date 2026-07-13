<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\LessonSectionDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\LessonSectionRequest;
use App\Models\Grade;
use App\Models\LessonSection;
use App\Models\Subject;
use Illuminate\Http\Request;
use App\Traits\HasStatusToggle;

class LessonSectionController extends Controller
{
    use HasStatusToggle;

    public function index(LessonSectionDataTable $dataTable, Subject $subject)
    {
        return $dataTable->with('subject', $subject)->render('dashboard.admin.lesson_sections.index', [
            'subject' => $subject
        ]);
    }

    public function create(Subject $subject)
    {
        return view('dashboard.admin.lesson_sections.create', compact('subject'));
    }

    public function store(LessonSectionRequest $request, Subject $subject)
    {

        $maxOrder = $subject->lessonSections()->max('order_by');
        $data = $request->validated();
        $data['order_by'] = $maxOrder ? $maxOrder + 1 : 1;
        $subject->lessonSections()->create($request->validated());

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

    public function sort(Request $request, $subjectId)
    {
        foreach ($request->order as $item) {
            LessonSection::where('id', $item['id'])
                ->where('subject_id', $subjectId) // 🔒 تأكد أنه لن يعدل إلا على أقسام هذه المادة
                ->update(['order_by' => $item['order_by']]);
        }

        return response()->json(['status' => 'success']);
    }
}

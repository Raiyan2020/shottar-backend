<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ExamDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExamRequest;
use App\Models\Exam;
use App\Models\Subject;
use App\Traits\HasStatusToggle;
use App\Traits\ImageTrait;

class ExamController extends Controller
{
    use HasStatusToggle, ImageTrait {
        HasStatusToggle::toggleIsFree as toggleModelIsFree;
    }

    public function index(ExamDataTable $dataTable, Subject $subject)
    {
        return $dataTable
            ->with('subject', $subject)
            ->render('dashboard.admin.exams.index', compact('subject'));
    }

    public function create(Subject $subject)
    {
        $sections = $subject->lessonSections()->get();

        return view('dashboard.admin.exams.create', compact('subject', 'sections'));
    }

    public function store(ExamRequest $request, Subject $subject)
    {
        $data = $request->validated();
        unset($data['file']);

        $data['subject_id'] = $subject->id;
        $data['status'] = $request->boolean('status', true);
        $data['is_free'] = $request->boolean('is_free', false);
        $data['uploaded_by'] = auth('admin')->id();
        $data['order_by'] = ((int) $subject->exams()->max('order_by')) + 1;

        if ($request->hasFile('file')) {
            $data['file'] = $this->uploadPdf($request->file('file'), 'exams');
        }

        Exam::create($data);

        return redirect()
            ->route('admin.subjects.exams.index', $subject->id)
            ->with('success', __('general.Exam created successfully'));
    }

    public function edit(Subject $subject, Exam $exam)
    {
        abort_unless($exam->subject_id === $subject->id, 404);

        $sections = $subject->lessonSections()->get();

        return view('dashboard.admin.exams.edit', compact('subject', 'exam', 'sections'));
    }

    public function update(ExamRequest $request, Subject $subject, Exam $exam)
    {
        abort_unless($exam->subject_id === $subject->id, 404);

        $data = $request->validated();
        unset($data['file']);

        $data['status'] = $request->boolean('status', $exam->status);
        $data['is_free'] = $request->boolean('is_free', $exam->is_free);

        if ($request->hasFile('file')) {
            $this->deleteImage($exam->file);
            $data['file'] = $this->uploadPdf($request->file('file'), 'exams');
        }

        $exam->update($data);

        return redirect()
            ->route('admin.subjects.exams.index', $subject->id)
            ->with('success', __('general.Exam updated successfully'));
    }

    public function destroy(Subject $subject, Exam $exam)
    {
        abort_unless($exam->subject_id === $subject->id, 404);

        $this->deleteImage($exam->file);
        $exam->delete();

        return response()->json('success');
    }

    public function toggleStatus($id)
    {
        return $this->toggleStatu(Exam::class, $id);
    }

    public function toggleIsFree($id)
    {
        return $this->toggleModelIsFree(Exam::class, $id);
    }
}

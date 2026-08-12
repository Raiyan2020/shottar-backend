<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\SubjectDataTable;
use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\StudyType;
use App\Traits\HasStatusToggle;
use App\Http\Requests\SubjectRequest;
use App\Models\Subject;
use App\Rules\IosProductIdRule;
use App\Traits\ImageTrait;

class SubjectController extends Controller
{
    use HasStatusToggle, ImageTrait;

    public function index(SubjectDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.subjects.index');
    }

    public function create()
    {
        $grades = Grade::where('status', 1)->get();
        $studyTypes = StudyType::where('status', 1)->get();
        $semesters = Semester::where('status', 1)->get();

        return view('dashboard.admin.subjects.create', compact(
            'grades',
            'studyTypes',
            'semesters'
        ));
    }

    public function store(SubjectRequest $request)
    {
        $data = $request->validated();
        unset($data['image']);

        $semesterIds = $data['semester_ids'] ?? [];
        unset($data['semester_ids']);
        $data['semester_id'] = $semesterIds[0] ?? null;

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadImage('public', $request->file('image'));
        }

        $subject = Subject::create($data);
        $subject->semesters()->sync($semesterIds);

        return redirect()->route('admin.subjects.index')->with('success', 'Subject created successfully');
    }

    public function edit(Subject $subject)
    {
        $subject->load('semesters');
        $grades = Grade::where('status', 1)->get();
        $studyTypes = StudyType::where('status', 1)->get();
        $semesters = Semester::where('status', 1)->get();

        $iosProductLocked = IosProductIdRule::isLocked($subject->ios_product_id);

        return view('dashboard.admin.subjects.edit', compact('subject', 'grades', 'studyTypes', 'semesters', 'iosProductLocked'));
    }

    public function update(SubjectRequest $request, Subject $subject)
    {
        $data = $request->validated();
        unset($data['image']);

        $semesterIds = $data['semester_ids'] ?? [];
        unset($data['semester_ids']);
        if (! empty($semesterIds)) {
            $data['semester_id'] = $semesterIds[0];
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($subject->getRawOriginal('image') ?? $subject->image);
            $data['image'] = $this->uploadImage('public', $request->file('image'));
        }

        $subject->update($data);
        $subject->semesters()->sync($semesterIds);

        return redirect()->route('admin.subjects.index')->with('success', 'Subject updated successfully');
    }

    public function destroy(Subject $subject)
    {
        if ($subject->image) {
            $this->deleteImage($subject->getRawOriginal('image') ?? $subject->image);
        }
        $subject->delete();

        return response()->json('success');
    }

    public function toggleStatus($id)
    {
        return $this->toggleStatu(Subject::class, $id);
    }
}

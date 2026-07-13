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
use App\Traits\ImageTrait;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    use HasStatusToggle , ImageTrait;

    public function index(SubjectDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.subjects.index');
    }

    public function create()
    {
        $grades = Grade::where('status', 1)->get();
        $studyTypes = StudyType::where('status', 1)->get();
        $semesters = Semester::where('status', 1)->get();
        return view('dashboard.admin.subjects.create' , compact(
            'grades',
            'studyTypes',
            'semesters'
        ));
    }

    public function store(SubjectRequest $request)
    {
        // معالجة رفع الصورة إذا وجدت
        $data = $request->validated();
        if ($request->has('image')) {
            $image_path = $this->uploadImage('admin', $request->image);
        }
        $data['image'] = $image_path ?? null; // إذا لم تكن الصورة موجودة، اجعلها فارغة

        Subject::create($data);
        return redirect()->route('admin.subjects.index')->with('success', 'Subject created successfully');
    }

    public function edit(Subject $subject)
    {
        $grades = Grade::where('status', 1)->get();
        $studyTypes = StudyType::where('status', 1)->get();
        $semesters = Semester::where('status', 1)->get();
        return view('dashboard.admin.subjects.edit', compact('subject', 'grades', 'studyTypes', 'semesters'));
    }

    public function update(SubjectRequest $request, Subject $subject)
    {
        $data = $request->validated();
        if ($request->has('image')) {
            $image_path = $this->uploadImage('admin', $request->image);
            $data['image'] = $image_path ?? null;
        }

        $subject->update($data);
        return redirect()->route('admin.subjects.index')->with('success', 'Subject updated successfully');
    }

    public function destroy(Subject $subject)
    {
        // حذف الصورة إذا كانت موجودة
        if ($subject->image) {
            $this->deleteImage($subject->image);
        }
        $subject->delete();

        return response()->json('success');
    }

    public function toggleStatus($id)
    {
        return $this->toggleStatu(Subject::class, $id);
    }
}

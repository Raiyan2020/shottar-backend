<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\SubjectDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubjectRequest;
use App\Models\CoreSubject;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\StudyType;
use App\Models\Subject;
use App\Traits\HasStatusToggle;
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
        $coreSubjects = CoreSubject::where('status', 1)->orderBy('name_ar')->get();

        return view('dashboard.admin.subjects.create', compact(
            'grades',
            'studyTypes',
            'semesters',
            'coreSubjects'
        ));
    }

    public function store(SubjectRequest $request)
    {
        $data = $request->validated();
        $semesterIds = $data['semester_ids'] ?? [];
        unset($data['semester_ids']);
        $data['semester_id'] = $semesterIds[0] ?? null;

        $data = array_merge($data, $this->attributesFromCoreSubject((int) $data['core_subject_id']));

        $subject = Subject::create($data);
        $subject->semesters()->sync($semesterIds);
        $this->ensureIosProductId($subject);

        return redirect()->route('admin.subjects.index')->with('success', 'Subject created successfully');
    }

    public function edit(Subject $subject)
    {
        $subject->load('semesters');
        $grades = Grade::where('status', 1)->get();
        $studyTypes = StudyType::where('status', 1)->get();
        $semesters = Semester::where('status', 1)->get();
        $coreSubjects = CoreSubject::where('status', 1)->orderBy('name_ar')->get();

        return view('dashboard.admin.subjects.edit', compact(
            'subject',
            'grades',
            'studyTypes',
            'semesters',
            'coreSubjects',
        ));
    }

    public function update(SubjectRequest $request, Subject $subject)
    {
        $data = $request->validated();
        $semesterIds = $data['semester_ids'] ?? [];
        unset($data['semester_ids']);
        if (! empty($semesterIds)) {
            $data['semester_id'] = $semesterIds[0];
        }

        $data = array_merge($data, $this->attributesFromCoreSubject((int) $data['core_subject_id']));

        $subject->update($data);
        $subject->semesters()->sync($semesterIds);
        $this->ensureIosProductId($subject->fresh());

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

    /**
     * نسخ الاسم والصورة من المادة الأساسية للحفاظ على توافق الـ API.
     */
    protected function attributesFromCoreSubject(int $coreSubjectId): array
    {
        $core = CoreSubject::findOrFail($coreSubjectId);

        return [
            'name_ar' => $core->name_ar,
            'name_en' => $core->name_en ?: $core->name_ar,
            'image' => $core->getRawOriginal('image') ?? $core->image,
        ];
    }

    protected function iosProductIdForSubject(int $subjectId): string
    {
        return 'com.raiyansoft.shottar.subject.'.$subjectId;
    }

    protected function ensureIosProductId(Subject $subject): void
    {
        if ($subject->ios_product_id) {
            return;
        }

        $subject->update([
            'ios_product_id' => $this->iosProductIdForSubject($subject->id),
        ]);
    }
}

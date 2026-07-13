<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\AdminsDataTable;
use App\DataTables\TeachersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Admin;
use App\Models\Subject;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    use ImageTrait;


    public function index(TeachersDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.teachers.index');
    }

    public function create()
    {
        $subjects = Subject::with('grade','semester')->get();
//        return $subjects;
        return view('dashboard.admin.teachers.create', compact('subjects'));
    }

    public function store(TeacherRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {

                // صورة (انتبه استخدم hasFile)
                $image_path = null;
                if ($request->hasFile('photo')) {
                    $image_path = $this->uploadImage('admin', $request->file('photo'));
                }

                // إنشاء المعلّم
                $teacher = Admin::create([
                    'name'       => $request->name,
                    'email'      => $request->email,
                    'password'   => Hash::make($request->password),
                    'image'      => $image_path,
                    'roles_name' => json_encode(['teacher']), // اختياري للتوافق القديم
                ]);

                // دور Spatie
                $teacher->assignRole('teacher');

                // IDs المواد المختارة من الـ Select2
                $subjectIds = $request->input('subject_ids', []); // اسم الحقل في الفورم

                // ربط المواد (يحذف/يضيف تلقائياً بدون تكرار)
                $teacher->teachingSubjects()->sync($subjectIds);
            });

            session()->flash('success', __('messages.created successfully.'));
            return redirect()->route('admin.teachers.index');

        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', __('messages.An error occurred while creating the teacher.'));
            return back()->withInput();
        }
    }

    public function edit($id)
    {
        try {
            $teacher = Admin::findOrFail($id);
            if (!$teacher->hasRole('teacher')) {
                session()->flash('error', 'هذا المستخدم ليس معلماً.');
                return redirect()->route('admin.teachers.index');
            }
            $subjects = Subject::with('grade','semester')->get();


            $current = DB::table('teacher_subjects')
                ->where('teacher_id', $teacher->id)->get();
            $selectedSubjectIds = $teacher->teachingSubjects()->pluck('subjects.id')->toArray();


            return view('dashboard.admin.teachers.edit', compact('teacher','subjects','current', 'selectedSubjectIds'));

        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', __('messages.There was an error try again'));
            return redirect()->route('admin.teachers.index');
        }
    }

    public function update(UpdateTeacherRequest $request, $id)
    {
        try {
            DB::transaction(function () use ($request, $id) {
                $teacher = Admin::findOrFail($id);

                // تأكيد أن الحساب يحمل دور "teacher"
                if (! $teacher->hasRole('teacher')) {
                    $teacher->assignRole('teacher');
                }

                // صورة جديدة؟ (استخدم hasFile/file)
                $imagePath = $teacher->image;
                if ($request->hasFile('photo')) {
                    $imagePath = $this->uploadImage('admin', $request->file('photo'));
                }

                // تحديث بيانات الحساب
                $payload = [
                    'name'  => $request->name,
                    'email' => $request->email,
                    'image' => $imagePath,
                ];

                if ($request->filled('password')) {
                    $payload['password'] = Hash::make($request->password);
                }

                $teacher->update($payload);

                // ربط المواد المختارة من الـ Select2
                // fallback بسيط في حال ما زال يُرسل assignments قديماً
                $subjectIds = $request->input('subject_ids', []); // اسم الحقل في الفورم

                // sync يحذف/يضيف تلقائياً بدون تكرار
                $teacher->teachingSubjects()->sync($subjectIds);
            });

            session()->flash('success', __('messages.updated successfully.'));
            return redirect()->route('admin.teachers.index');

        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', __('messages.here was an error try again'));
            return back()->withInput();
        }
    }

    public function destroy(Admin $teacher)
    {
        try {
            DB::transaction(function () use ($teacher) {
                // تنظيف الربط قبل حذف الحساب
                DB::table('teacher_subjects')->where('teacher_id', $teacher->id)->delete();
                // لو بدك فقط تلغي دوره بدون حذف الحساب، علّق السطر التالي
                $teacher->delete();
            });

            return response()->json('success');

        } catch (\Throwable $e) {
            report($e);
            return response()->json('error', 500);
        }
    }
    //showSections
    public function showSections(Subject $subject)
    {
        // تأكد أن المادة مربوطة بالمعلم الحالي
        if (!$subject->teachers()->where('teacher_id', auth()->id())->exists()) {
            abort(403);
        }

        $sections = $subject->sections; // الأقسام التابعة لها
//        return $sections;
        return view('dashboard.teacher.sections.index', compact('subject', 'sections'));
    }

}

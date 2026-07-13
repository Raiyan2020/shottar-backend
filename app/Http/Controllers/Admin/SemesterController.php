<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\SemesterDataTable;
use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\StudyType;
use App\Traits\HasStatusToggle;
use App\Http\Requests\SemesterRequest;
use App\Models\Semester;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    use HasStatusToggle;

    public function index(SemesterDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.semesters.index');
    }

    public function create()
    {

        return view('dashboard.admin.semesters.create');
    }

    public function store(SemesterRequest $request)
    {
        Semester::create($request->validated());
        return redirect()->route('admin.semesters.index')->with('success', 'created successfully');
    }

    public function edit(Semester $semester)
    {
        return view('dashboard.admin.semesters.edit', compact('semester'));
    }

    public function update(SemesterRequest $request, Semester $semester)
    {
        $semester->update($request->validated());
        return redirect()->route('admin.semesters.index')->with('success', 'updated successfully');
    }

    public function destroy(Semester $semester)
    {
        $semester->delete();
        return response()->json('success');
    }

    public function toggleStatus($id)
    {
        return $this->toggleStatu(Semester::class, $id);
    }
}

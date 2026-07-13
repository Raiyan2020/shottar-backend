<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\StudyTypeDataTable;
use App\Http\Controllers\Controller;
use App\Traits\HasStatusToggle;
use App\Http\Requests\StudyTypeRequest;
use App\Models\StudyType;
use Illuminate\Http\Request;

class StudyTypeController extends Controller
{
    use HasStatusToggle;

    public function index(StudyTypeDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.study-types.index');
    }

    public function create()
    {
        return view('dashboard.admin.study-types.create');
    }

    public function store(StudyTypeRequest $request)
    {
        StudyType::create($request->validated());
        return redirect()->route('admin.study-types.index')->with('success', 'created successfully');
    }

    public function edit(StudyType $studyType)
    {
        return view('dashboard.admin.study-types.edit', compact('studyType'));
    }

    public function update(StudyTypeRequest $request, StudyType $studyType)
    {
        $studyType->update($request->validated());
        return redirect()->route('admin.study-types.index')->with('success', 'updated successfully');
    }

    public function destroy(StudyType $studyType)
    {
        $studyType->delete();
        return response()->json('success');
    }

    public function toggleStatus($id)
    {
        return $this->toggleStatu(StudyType::class, $id);
    }
}

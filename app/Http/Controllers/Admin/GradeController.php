<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\GradeDataTable;
use App\Http\Controllers\Controller;
use \App\Traits\HasStatusToggle;
use App\Http\Requests\GradeRequest;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    use HasStatusToggle;
    public function index(GradeDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.grades.index');
    }

    public function create()
    {
        return view('dashboard.admin.grades.create');
    }

    public function store(GradeRequest $request)
    {
        $data = $request->validated();

        $lastOrder = Grade::max('order_by') ?? 0;
        $data['order_by'] = $lastOrder + 1;

        Grade::create($data);

        return redirect()->route('admin.grades.index')->with('success', 'Grade created successfully');
    }

    public function edit(Grade $grade)
    {
        return view('dashboard.admin.grades.edit', compact('grade'));
    }

    public function update(GradeRequest $request, Grade $grade)
    {
        $grade->update($request->validated());
        return redirect()->route('admin.grades.index')->with('success', 'Grade updated successfully');
    }

    public function destroy(Grade $grade)
    {
        $grade->delete();

        return response()->json('success');
    }
    public function toggleStatus($id)
    {
        return $this->toggleStatu(Grade::class, $id);
    }
    public function sort(Request $request)
    {
        foreach ($request->order as $item) {
            Grade::where('id', $item['id'])->update(['order_by' => $item['order_by']]);
        }

        return response()->json(['status' => true]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\CoreSubjectDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\CoreSubjectRequest;
use App\Models\CoreSubject;
use App\Traits\HasStatusToggle;
use App\Traits\ImageTrait;

class CoreSubjectController extends Controller
{
    use HasStatusToggle, ImageTrait;

    public function index(CoreSubjectDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.core-subjects.index');
    }

    public function create()
    {
        return view('dashboard.admin.core-subjects.create');
    }

    public function store(CoreSubjectRequest $request)
    {
        $data = $request->validated();
        unset($data['image']);
        $data['status'] = $request->boolean('status', true);

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadImage('public', $request->file('image'));
        }

        CoreSubject::create($data);

        return redirect()->route('admin.core-subjects.index')
            ->with('success', __('general.Core subject created successfully'));
    }

    public function edit(CoreSubject $coreSubject)
    {
        return view('dashboard.admin.core-subjects.edit', compact('coreSubject'));
    }

    public function update(CoreSubjectRequest $request, CoreSubject $coreSubject)
    {
        $data = $request->validated();
        unset($data['image']);
        $data['status'] = $request->boolean('status', true);

        if ($request->hasFile('image')) {
            $this->deleteImage($coreSubject->getRawOriginal('image') ?? $coreSubject->image);
            $data['image'] = $this->uploadImage('public', $request->file('image'));
        }

        $coreSubject->update($data);

        return redirect()->route('admin.core-subjects.index')
            ->with('success', __('general.Core subject updated successfully'));
    }

    public function destroy(CoreSubject $coreSubject)
    {
        if ($coreSubject->image) {
            $this->deleteImage($coreSubject->getRawOriginal('image') ?? $coreSubject->image);
        }
        $coreSubject->delete();

        return response()->json('success');
    }

    public function toggleStatus($id)
    {
        return $this->toggleStatu(CoreSubject::class, $id);
    }
}

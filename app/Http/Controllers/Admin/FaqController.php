<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\FaqDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\FaqRequest;
use App\Models\Faq;
use App\Traits\HasStatusToggle;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    use HasStatusToggle;

    public function index(FaqDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.faqs.index');
    }

    public function create()
    {
        return view('dashboard.admin.faqs.create');
    }

    public function store(FaqRequest $request)
    {
        Faq::create($request->validated());
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ created successfully');
    }

    public function edit(Faq $faq)
    {
        return view('dashboard.admin.faqs.edit', compact('faq'));
    }

    public function update(FaqRequest $request, Faq $faq)
    {
        $faq->update($request->validated());
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ updated successfully');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return response()->json('success');
    }

    public function toggleStatus($id)
    {
        return $this->toggleStatu(Faq::class, $id);
    }
}

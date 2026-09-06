<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\GradeDataTable;
use App\Http\Controllers\Controller;
use \App\Traits\HasStatusToggle;
use App\Traits\HandlesRowOrdering;
use App\Http\Requests\GradeRequest;
use App\Models\Grade;
use App\Services\RowOrderService;
use App\Models\IosBundleProduct;
use App\Models\Semester;
use App\Rules\IosProductIdRule;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    use HasStatusToggle, HandlesRowOrdering;
    public function index(GradeDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admin.grades.index');
    }

    public function create()
    {
        $semesters = Semester::where('status', 1)->get();

        return view('dashboard.admin.grades.create', compact('semesters'));
    }

    public function store(GradeRequest $request)
    {
        $data = $request->validated();
        $iosProductIds = $data['ios_product_ids'] ?? [];
        unset($data['ios_product_ids']);

        $lastOrder = Grade::max('order_by') ?? 0;
        $data['order_by'] = $lastOrder + 1;

        $grade = Grade::create($data);
        $this->syncIosBundles($grade, $iosProductIds);

        return redirect()->route('admin.grades.index')->with('success', 'Grade created successfully');
    }

    public function edit(Grade $grade)
    {
        $grade->load('iosBundleProducts');
        $semesters = Semester::where('status', 1)->get();
        $lockedIosProductIds = $grade->iosBundleProducts
            ->pluck('ios_product_id')
            ->filter()
            ->filter(fn ($id) => IosProductIdRule::isLocked($id))
            ->values()
            ->all();

        return view('dashboard.admin.grades.edit', compact('grade', 'semesters', 'lockedIosProductIds'));
    }

    public function update(GradeRequest $request, Grade $grade)
    {
        $data = $request->validated();
        $iosProductIds = $data['ios_product_ids'] ?? [];
        unset($data['ios_product_ids']);

        $grade->update($data);
        $this->syncIosBundles($grade, $iosProductIds);

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
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*.id' => ['required', 'integer', 'distinct'],
        ]);

        // بيحافظ على المراكز العامة بدل ما يرقّم الصفوف الظاهرة من 1.
        app(RowOrderService::class)->applyVisibleOrder(
            fn () => Grade::query(),
            collect($data['order'])->pluck('id')->all()
        );

        return response()->json(['status' => 'success']);
    }

    /**
     * نقل صف مركز واحد في الترتيب العام. نطاق الترتيب هنا هو الجدول كله.
     */
    public function move(Request $request, Grade $grade)
    {
        return $this->moveRow($request, $grade, fn () => Grade::query());
    }

    protected function syncIosBundles(Grade $grade, array $ids): void
    {
        foreach ($ids as $semesterId => $productId) {
            $productId = trim((string) $productId);
            if ($productId === '') {
                IosBundleProduct::where('grade_id', $grade->id)
                    ->where('semester_id', $semesterId)
                    ->delete();
                continue;
            }

            IosBundleProduct::updateOrCreate(
                ['grade_id' => $grade->id, 'semester_id' => $semesterId],
                ['ios_product_id' => $productId]
            );
        }
    }
}

<?php

namespace App\DataTables;

use App\Models\Grade;
use App\Services\RowOrderService;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class GradeDataTable extends DataTable
{
    protected string $statusRoute = 'admin.grades.toggleStatus';
    /** نطاق ترتيب الصفوف: الجدول كله. */
    protected function orderScope(): \Closure
    {
        return fn () => Grade::query();
    }

    public function dataTable($query): EloquentDataTable
    {
        // المراكز العامة بتتجاب مرة واحدة، فتفضل صح مهما كانت الصفحة أو البحث.
        $order = app(RowOrderService::class)->positionMap($this->orderScope());

        return (new EloquentDataTable($query))
            ->addColumn('reorder', function ($grade) use ($order) {
                return view('dashboard.partials._reorder-cell', [
                    'moveUrl' => route('admin.grades.move', $grade->id),
                    'position' => $order['positions'][$grade->id] ?? 1,
                    'total' => $order['total'],
                ])->render();
            })
            ->addColumn('action', function ($grade) {
                return view('components.datatable.actions', [
                    'id' => $grade->id,
                    'routeEdit' => 'admin.grades.edit',
                    'routeDelete' => 'admin.grades.destroy',
                    'name' => $grade->name_ar,
                ]);
            })


            ->editColumn('status', function ($model) {

                return view('components.datatable.status-toggle', [
                    'id' => $model->id,
                    'status' => $model->status,
                    'url' => route($this->statusRoute, $model->id),
                ]);
            })
            ->setRowId('id') // <-- مهم جدًا لتحديد ID للصف
            ->setRowAttr([
                'class' => 'sortable-row', // لسهولة استهدافه من الجافاسكربت
            ])
            ->rawColumns(['action', 'status', 'study_type', 'reorder']);
    }

    public function query(Grade $model)
    {
        return $model->newQuery()->orderBy('order_by', 'asc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('datatable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addTableClass('table table-hover')
            ->parameters([
                // نفس علاج LessonSectionDataTable: الترتيب الافتراضي بالـ id
                // كان بيلغي ترتيب order_by اللي جاي من الاستعلام، فالسحب مكانش
                // بيبان أصلاً. [] معناها سيب ترتيب الاستعلام زي ما هو.
                'order' => [],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('reorder')->title('')->exportable(false)->printable(false)
                ->searchable(false)->orderable(false)->addClass('reorder-col'),
            Column::make('id')->title(__('dataTable.id')),
            localeNameColumn(),
            Column::make('all_materials_price')->title(__('dataTable.all_materials_price')),
            Column::make('status')->title(__('dataTable.status')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'grades_' . date('YmdHis');
    }
}

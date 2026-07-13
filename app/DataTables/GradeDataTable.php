<?php

namespace App\DataTables;

use App\Models\Grade;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class GradeDataTable extends DataTable
{
    protected string $statusRoute = 'admin.grades.toggleStatus';
    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
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
            ->rawColumns(['action', 'status', 'study_type']);
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
            ->orderBy(0, 'desc')

            ->addTableClass('table table-hover');
    }

    public function getColumns(): array
    {
        return [
            Column::make('id')->title(__('dataTable.id')),
            Column::make('name_ar')->title(__('dataTable.name_ar')),
            Column::make('name_en')->title(__('dataTable.name_en')),
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

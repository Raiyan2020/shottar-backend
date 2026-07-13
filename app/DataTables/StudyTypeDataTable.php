<?php

namespace App\DataTables;

use App\Models\StudyType;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class StudyTypeDataTable extends DataTable
{
    protected string $statusRoute = 'admin.study-types.toggleStatus';

    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($studyType) {
                return view('components.datatable.actions', [
                    'id' => $studyType->id,
                    'routeEdit' => 'admin.study-types.edit',
                    'routeDelete' => 'admin.study-types.destroy',
                    'name' => $studyType->name_ar,
                ]);
            })
            ->editColumn('status', function ($model) {
                return view('components.datatable.status-toggle', [
                    'id' => $model->id,
                    'status' => $model->status,
                    'url' => route($this->statusRoute, $model->id),
                ]);
            })
            ->rawColumns(['action', 'status']);
    }

    public function query(StudyType $model)
    {
        return $model->newQuery();
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
            Column::make('status')->title(__('dataTable.status')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'study_types_' . date('YmdHis');
    }
}

<?php

namespace App\DataTables;

use App\Models\CoreSubject;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class CoreSubjectDataTable extends DataTable
{
    protected string $statusRoute = 'admin.core-subjects.toggleStatus';

    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($coreSubject) {
                return view('components.datatable.actions', [
                    'id' => $coreSubject->id,
                    'routeEdit' => 'admin.core-subjects.edit',
                    'routeDelete' => 'admin.core-subjects.destroy',
                    'name' => $coreSubject->name_ar,
                ]);
            })
            ->editColumn('image', function ($coreSubject) {
                return SubjectDataTable::imageHtml($coreSubject->image);
            })
            ->editColumn('status', function ($model) {
                return view('components.datatable.status-toggle', [
                    'id' => $model->id,
                    'status' => $model->status,
                    'url' => route($this->statusRoute, $model->id),
                ]);
            })
            ->rawColumns(['action', 'status', 'image']);
    }

    public function query(CoreSubject $model)
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
            ->addTableClass('table table-hover datatable--bold');
    }

    public function getColumns(): array
    {
        return [
            Column::make('id')->title(__('dataTable.id')),
            localeNameColumn(),
            Column::make('image')->title(__('dataTable.image')),
            Column::make('status')->title(__('dataTable.status')),
            Column::computed('action')->title(__('dataTable.action'))->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'core_subjects_' . date('YmdHis');
    }
}

<?php

namespace App\DataTables;

use App\Models\Challenge;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class ChallengesDataTable extends DataTable
{
    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($challenge) {
                return view('components.datatable.actions', [
                    'id' => $challenge->id,
                    'routeEdit' => 'admin.challenges.edit',
                    'routeDelete' => 'admin.challenges.destroy',
                    'name' => optional($challenge->subject)->name ?? __('No Subject'),
                ]);
            })
            ->editColumn('status', function ($challenge) {
                return view('components.datatable.status-toggle', [
                    'id' => $challenge->id,
                    'status' => $challenge->status,
                    'url' => route('admin.challenges.toggleStatus', $challenge->id),
                ]);
            })
            ->editColumn('subject_id', function ($challenge) {
                return optional($challenge->subject)->name_en ?? __('No Subject');
            })
            ->rawColumns(['action', 'status']);
    }

    public function query(Challenge $model)
    {
        return $model->newQuery()->with('subject');
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
            Column::make('subject_id')->title(__('dataTable.subject')),
            Column::make('status')->title(__('dataTable.status')),
            Column::computed('action')
                ->title(__('dataTable.action'))
                ->exportable(false)
                ->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'challenges_' . date('YmdHis');
    }
}
